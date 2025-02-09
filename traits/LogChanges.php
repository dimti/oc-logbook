<?php namespace Jacob\Logbook\Traits;

use Backend\Classes\AuthManager;
use Backend\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Jacob\LogBook\Classes\LoadRelation;
use Jacob\Logbook\Models\Log;
use Jacob\LogBook\Classes\Entities\Attribute;
use Jacob\LogBook\Classes\Entities\Changes;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use Config;

trait LogChanges
{
    /**
     * ===========================================================
     * You can override these properties in your model
     * ===========================================================
     */

    /**
     * @var array $ignoreFields fields to ignore
     *
     * protected $ignoreFieldsLogbook = [
     *      'updated_at'
     * ];
     */

    /**
     * Delete log book items after model is deleted
     *
     * If true -> log items are deleted when the model is deleted
     * If false -> a new log item will be created with status deleted.
     *
     * @var bool $deleteLogbookAfterDelete
     *
     * protected $deleteLogbookAfterDelete = false;
     */

    /**
     * Here you can override the model name that is displayed in the log files.
     * The name is going to be translated when possible.
     *
     * string $logBookModelName
     *
     * public $logBookModelName = 'MyModelName'
     */

    /**
     * Hides or shows undo button for current field
     *
     * @var bool $logBookLogUndoable
     *
     * public $logBookLogUndoable = false
     */

    /**
     * If you override this function you can change the value that is displayed in the log book
     * This can be useful for example with a dropdown
     *
     * @param $column
     * @param $value
     * @return string|Htmlable
     */
    public static function changeLogBookDisplayValue($column, $value)
    {
        return $value;
    }

    /**
     * If you override this function you can change the column name that is displayed in the log book
     * The returned column will be translated if it is possible
     *
     * @param string $column
     * @return string
     */
    public static function changeLogBookDisplayColumn($column)
    {
        return $column;
    }

    public static function bootLogChanges(): void
    {
        static::extend(function(Model $model) {
            /** @var Model|self $model */
            $model->bindEvent('model.afterCreate', function() use ($model) {
                $model->logChangesAfterCreate();
            });

            $model->bindEvent('model.afterUpdate', function() use ($model) {
                $model->logChangesAfterUpdate();
            });

            $model->bindEvent('model.afterDelete', function() use ($model) {
                $model->logChangesAfterDelete();
            });
        });
    }

    private function createLogBookLogItem(Changes $changes): void
    {
        /** @var User $user */
        $user = AuthManager::instance()->getUser();

        if (!$user) {
            $backendUserId = null;
        } else {
            $backendUserId = $user->getKey();
        }

        Log::create([
            'model' => get_class($this),
            'model_key' => $this->getKey(),
            'changes' => $changes->getData(),
            'backend_user_id' => $backendUserId,
        ]);

        $this->processRetention();
    }

    private function processRetention()
    {
        if (Config::get('jacob.logbook::prune')) {
            if (Config::get('jacob.logbook::retention.max_records') > 0) {
                $latestOfMaxRecordsId = Log::whereModel(get_class($this))->whereModelKey($this->getKey())
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->orderBy('id', 'desc')
                    ->limit(Config::get('jacob.logbook::retention.max_records'))
                    ->select(['id'])
                    ->get()->last()->id;

                Log::whereModel(get_class($this))->whereModelKey($this->getKey())
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->orderBy('id', 'desc')
                    ->where('id', '<', $latestOfMaxRecordsId)
                    ->forceDelete();
            }

            if (Config::get('jacob.logbook::retention.max_records') > 0) {
                Log::whereModel(get_class($this))->whereModelKey($this->getKey())
                    ->withoutGlobalScope(SoftDeletingScope::class)
                    ->where('created_at', '<', date('Y-m-d', time() - 30 * 3600 * 24))
                    ->forceDelete();
            }
        }
    }

    public function logChangesAfterCreate(): void
    {
        $changes = new Changes(Changes::TYPE_CREATED);

        $this->createLogBookLogItem($changes);
    }

    public function logChangesAfterUpdate(): void
    {
        $attributes = [];

        $originalAttributes = $this->getOriginal();

        $ignoreFieldsLogbook = $this->ignoreFieldsLogbook ?? ['updated_at'];

        foreach ($this->getDirty() as $column => $newValue) {
            if (in_array($column, $ignoreFieldsLogbook) || (in_array($column, $this->jsonable) && !$originalAttributes[$column])) {
                continue; //ignore field if presented appropriate list or ignore if jsonable field without old value
            }

            $diffJsonableValues = [];

            if (in_array($column, $this->jsonable)) {
                $oldValueDecoded = json_decode($originalAttributes[$column]);

                $newValueDecoded = json_decode($newValue);

                foreach ($newValueDecoded as $key => $value) {
                    if (!isset($oldValueDecoded->$key) || $oldValueDecoded->$key != $value) {
                        $diffJsonableValues[$key] = [
                            'old' => $oldValueDecoded->$key ?? null,
                            'new' => $value,
                        ];
                    }
                }
            }

            $attributes[] = new Attribute($column,$originalAttributes[$column] ?? null, $newValue, $diffJsonableValues);
        }

        if (count($attributes) === 0) {
            // no changes to log
            return;
        }

        $this->createLogBookLogItem(new Changes(Changes::TYPE_UPDATED, $attributes));
    }

    public function logChangesAfterDelete(): void
    {
        if ($this->deleteLogbookAfterDelete ?? false) {
            /** @var Builder $query */
            $query = Log::query()->where('model', '=', get_class($this));
            $query->where('model_key', '=', $this->getKey());
            $query->delete();
        } else {
            $changes = new Changes(Changes::TYPE_DELETED);

            $this->createLogBookLogItem($changes);
        }
    }

    /**
     * @param int $limitPerPage
     * @param int $currentPage
     * @param LoadRelation[]|null $relations
     * @return LengthAwarePaginator
     */
    public function getLogsFromLogBook(int $limitPerPage = 20, int $currentPage = 0, array $relations = null): LengthAwarePaginator
    {
        /** @var Builder $query */
        $query = Log::query()->where([
            ['model', '=', get_class($this)],
            ['model_key', '=', $this->getKey()]
        ]);

        if ($relations !== null) {
            foreach ($relations as $relation) {
                $relationClass = null;

                $relationName = $relation->getName();

                if ($relation->isWithTrashed()) {
                    $relatedModels = $this->$relationName()->withTrashed()->get();
                } else {
                    $relatedModels = $this->$relationName;
                }

                // no related items found
                if ($relatedModels === null ) {
                    continue;
                }

                // one related item found
                if ($relatedModels instanceof Model) {
                    $query->orWhere([
                        ['model', '=', get_class($relatedModels)],
                        ['model_key', '=', $relatedModels->getKey()]
                    ]);
                    continue;
                }

                // multiple related items found
                /** @var Model $relatedModel */
                foreach ($relatedModels as $relatedModel) {
                    if ($relationClass === null) {
                        $relationClass = get_class($relatedModel);
                    }

                    $query->orWhere([
                        ['model', '=', $relationClass],
                        ['model_key', '=', $relatedModel->getKey()]
                    ]);
                }
            }
        }

        $query->orderBy('updated_at', 'desc');
        $query->with('backendUser');

        return $query->paginate($limitPerPage, $currentPage);
    }
}
