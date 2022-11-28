<?php

namespace Jacob\LogBook\Classes\Entities;

use Jacob\Logbook\Traits\LogChanges;

class Attribute extends BaseEntity
{
    protected $column;
    protected $old;
    protected $new;

    /**
     * @var array|null
     * @example ['key' => ['old' => 'old', 'new' => 'new']]
     * @see LogChanges::logChangesAfterUpdate
     */
    protected $diffJson;

    public function __construct(string $column, $old, $new, ?array $diffJson)
    {
        $this->column = $column;
        $this->old = $old;
        $this->new = $new;
        $this->diffJson = $diffJson;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getOld()
    {
        return $this->old;
    }

    public function getNew()
    {
        return $this->new;
    }

    public function hasDiffJson(): bool
    {
        return !!$this->diffJson;
    }

    public function getDiffJson(): array
    {
        return $this->diffJson;
    }
}
