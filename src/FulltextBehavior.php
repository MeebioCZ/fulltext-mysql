<?php

namespace Ypsylon\Propel\Behavior\Fulltext;

use Propel\Generator\Behavior\I18n\I18nBehavior;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\Column;
use Propel\Generator\Model\Index;
use Propel\Generator\Model\Table;
use Propel\Runtime\Exception\PropelException;

class FulltextBehavior extends Behavior
{
    public const PARAM_NAME = 'name';
    public const PARAM_COLUMNS = 'columns';
    public const PARAM_WEIGHTS = 'weights';

    // default parameters value
    protected $parameters = [
        self::PARAM_NAME => '',
        self::PARAM_COLUMNS => '',
        self::PARAM_WEIGHTS => '',
    ];

    /** @var array|\Ypsylon\Propel\Behavior\Fulltext\FulltextColumnInfo[] */
    protected $columnWeightList = [];

    private $isUsingI18nTable = null;

    public function queryMethods($builder) {
        $script = '';
        $script .= $this->addFromLocalizedArray();
    }

    protected function getNameFromParameters()
    {
        return $this->getParameter('name');
    }

    protected function getColumnsFromParameters()
    {
        $columnsParam = $this->getParameter('fulltext_columns');
        return $this->parseParamsFromString($columnsParam);
    }

    protected function getWeightsFromParameters()
    {
        $weightsParam = $this->getParameter('fulltext_weights');
        return $this->parseParamsFromString($weightsParam);
    }

    protected function fillColumnWeightList(): void
    {
        $columns = $this->getColumnsFromParameters();
        $weights = $this->getWeightsFromParameters();

        foreach ($columns as $key => $column) {
            $this->columnWeightList[] = new FulltextColumnInfo($this->getColumnFromName($column), $weights[$key] ?? 1);
        }
    }

    protected function getColumnFromName(string $name): Column
    {
        $i18nBehavior = $this->getI18nBehavior();
        if ($this->isColumnInTable($this->getTable(), $name)) {
            return $this->getTable()->getColumn($name);
        } else {
            return $i18nBehavior->getI18nTable()->getColumn($name);
        }
    }

    protected function parseParamsFromString($string): array
    {
        $params = explode(',', $string);
        foreach ($params as $key => $value) {
            $val = trim($value);
            if (strlen($val) > 0) {
                $params[$key] = $val;
            } else {
                unset($params[$key]);
            }
        }

        return array_values($params);
    }

    public function modifyTable()
    {
        parent::modifyTable();

        $this->checkColumns();
        $this->fillColumnWeightList();
        $this->createIndices();
    }

    protected function createIndex(Table $table, string $columnName): void
    {
        $fulltext = new Fulltext($this->getNameFromParameters());
        $fulltext->addColumn($table->getColumn($columnName));

        $table->addIndex($fulltext);
    }

    protected function createIndices(): void
    {
        $i18nBehavior = $this->getI18nBehavior();
        foreach ($this->columnWeightList as $column => $weight) {
            if ($this->isColumnInTable($this->getTable(), $column)) {
                $this->createIndex($this->getTable(), $column);
            } else {
                $this->createIndex($i18nBehavior->getI18nTable(), $column);
            }
        }
    }

    protected function getI18nBehavior(): ?I18nBehavior
    {
        foreach ($this->getTable()->getBehaviors() as $behavior) {
            if ($behavior instanceof I18nBehavior) {
                return $behavior;
            }
        }

        return null;
    }

    protected function checkColumns(): void
    {
        $columns = $this->getColumnsFromParameters();
        if (count($columns) === 0) {
            throw new PropelException('Fulltext behavior do not have any columns specified');
        }

        $i18nTable = $this->getI18nBehavior() ? $this->getI18nBehavior()->getI18nTable() : null;
        foreach ($columns as $column) {
            if ($this->isColumnInTable($this->getTable(), $column) || $this->isColumnInTable($i18nTable, $column)) {
                throw new PropelException('Unknown column ' . $column);
            }
        }
    }

    protected function isColumnInTable(Table $table, string $column): bool
    {
        return $table && $table->getColumn($column) !== null;
    }

    protected function isUsingI18nTable(): bool
    {
        if ($this->isUsingI18nTable !== null) {
            return $this->isUsingI18nTable;
        }

        $table = $this->getTable();
        foreach ($this->columnWeightList as $columnInfo) {
            if ($columnInfo->getColumn()->getTable() !== $table) {
                return true;
            }
        }

        return false;
    }

    protected function getColumnName(Column $column): string
    {
        if ($column->getTable() === $this->getTable()) {
            return $column->getTable()->getPhpName() . '.' . $column->getPhpName();
        }

        return $this->getTable()->getPhpName() . '.' . $column->getTable()->getPhpName() . '.' . $column->getPhpName();
    }

    protected function getColumnsNames(): array
    {
        $result = [];
        foreach ($this->columnWeightList as $columnInfo) {
            $result[] = $this->getColumnName($columnInfo->getColumn());
        }

        return $result;
    }

    protected function getColumnsWeights(): array
    {
        $result = [];
        foreach ($this->columnWeightList as $columnInfo) {
            $result[] = $columnInfo->getWeight();
        }

        return $result;
    }

    protected function addFulltextQuery()
    {
        return $this->renderTemplate('filterByFulltext', [
            'columns' => $this->getColumnsNames()
        ]);
    }

    protected function addFulltextOrder()
    {
        return $this->renderTemplate('orderByFulltext', [
            'columns' => $this->getColumnsWeights()
        ]);
    }
}