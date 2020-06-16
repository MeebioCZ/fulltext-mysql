<?php

namespace Ypsylon\Propel\Behavior\Fulltext;

use Propel\Generator\Model\Column;

class FulltextColumnInfo
{
    /** @var \Propel\Generator\Model\Column */
    protected $column;

    /** @var float */
    protected $weight;

    public function __construct(Column $column, float $weight)
    {
        $this->column = $column;
        $this->weight = $weight;
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
}