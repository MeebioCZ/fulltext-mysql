<?php

namespace Ypsylon\Propel\Behavior\Fulltext;

use Propel\Generator\Model\Column;

class FulltextColumnInfo
{
    /** @var \Propel\Generator\Model\Column */
    protected $column;

    /** @var float */
    protected $weight;

    /** @var bool */
    protected $isDelegatedColumn;

    public function __construct(Column $column, float $weight, bool $isDelegatedColumn)
    {
        $this->column = $column;
        $this->weight = $weight;

        $this->isDelegatedColumn = $isDelegatedColumn;
    }

    public function getColumn(): Column
    {
        return $this->column;
    }

    public function setColumn(Column $column): void
    {
        $this->column = $column;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function isDelegatedColumn(): bool
    {
        return $this->isDelegatedColumn;
    }

    public function setIsDelegatedColumn(bool $isDelegatedColumn): void
    {
        $this->isDelegatedColumn = $isDelegatedColumn;
    }
}