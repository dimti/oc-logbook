<?php

namespace Jacob\Logbook\Models;

use Backend\Models\User;
use Carbon\Carbon;
use Jacob\LogBook\Classes\Entities\Attribute;
use Jacob\LogBook\Classes\Entities\Changes;
use October\Rain\Database\Builder;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\SoftDelete;

/**
 * @property User $backendUser
 * @method Builder user()
 */
class Log extends Model
{
    use SoftDelete;

    public $table = 'jacob_logbook_logs';

    public $belongsTo = [
        'backendUser' => [
            User::class
        ],
    ];

    protected $guarded = ['*'];

    protected $fillable = [
        'model',
        'model_key',
        'changes',
        'backend_user_id',
    ];

    protected $jsonable = [
        'changes',
        'diff_json',
    ];

    public function getId(): int
    {
        return $this->getAttribute('id');
    }

    public function getModel(): string
    {
        return $this->getAttribute('model');
    }

    public function getModelKey(): string
    {
        return $this->getAttribute('model_key');
    }

    public function getMutation(): Changes
    {
        $changes = $this->getAttribute('changes');

        if (!is_array($changes)) {
            $this->delete();

            return new Changes(Changes::TYPE_UPDATED, []);
        }

        $attributes = $changes['changedAttributes'] !== null
            ? array_map(static function (array $attribute) {
                return new Attribute(
                    $attribute['column'] ?? '',
                    $attribute['old'] ?? null,
                    $attribute['new'] ?? null,
                    $attribute['diffJson'] ?? null
                );
            }, $changes['changedAttributes'])
            : null;

        return new Changes($changes['type'], $attributes);
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->getAttribute('created_at');
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->getAttribute('updated_at');
    }
}
