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

    protected $tableModificationOrder = 100;

    /** @var array|\Ypsylon\Propel\Behavior\Fulltext\FulltextColumnInfo[] */
    protected $columnWeightList = [];

    private $isUsingI18nTable = null;

    protected $duplicatedBehavior = false;

    public function queryMethods($builder) {
        $script = '';

        if ($this->hasAnyColumns()) {
            $script .= $this->addFulltextQuery();
            $script .= $this->addFulltextOrder();
        }

        return $script;
    }

    public function objectMethods()
    {
        $script = '';

        if ($this->hasAnyColumns()) {
            $script .= $this->addComputeFulltextValues();
        }

        return $script;
    }

    protected function getNameFromParameters()
    {
        return $this->getParameter(self::PARAM_NAME);
    }

    protected function getColumnsFromParameters()
    {
        $columnsParam = $this->getParameter(self::PARAM_COLUMNS);
        return $this->parseParamsFromString($columnsParam);
    }

    protected function getWeightsFromParameters()
    {
        $weightsParam = $this->getParameter(self::PARAM_WEIGHTS);
        return $this->parseParamsFromString($weightsParam);
    }

    protected function fillColumnWeightList(): void
    {
        $table = $this->getTable();

        $columns = $this->getColumnsFromParameters();
        $weights = $this->getWeightsFromParameters();

        $this->columnWeightList = [];
        foreach ($columns as $key => $column) {
            $col = $this->getColumnFromName($column);
            $this->columnWeightList[] = new FulltextColumnInfo($col, $weights[$key] ?? 1, $col->getTable() !== $table);
        }
    }

    protected function getColumnFromName(string $name): Column
    {
        $i18nBehavior = $this->getI18nBehavior();
        $i18nTable = $i18nBehavior ? $i18nBehavior->getI18nTable() : null;

        if ($this->isColumnInTable($i18nTable, $name)) {
            return $i18nTable->getColumn($name);
        } else {
            return $this->getTable()->getColumn($name);
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

        if ($this->isUsingI18nTable() && !$this->duplicatedBehavior) {
            $this->processI18nTables();
        }
    }

    protected function processI18nTables()
    {
        $i18nBehavior = $this->getI18nBehavior();
        if ($i18nBehavior) {

            $copy = clone $this;
            $copy->duplicatedBehavior = true;

            $copy->table = $i18nBehavior->getI18nTable();
            $i18nBehavior->getI18nTable()->addBehavior($copy);
        }
    }

    protected function createIndex(Table $table, Column $column): void
    {
        $fulltext = new Fulltext($this->getNameFromParameters());
        $fulltext->addColumn($column);

        $table->addIndex($fulltext);
    }

    protected function createIndices(): void
    {
        foreach ($this->columnWeightList as $columnInfo) {
            if ($this->isColumnInTable($this->getTable(), $columnInfo->getColumn()->getName())) {
                $this->createIndex($this->getTable(), $columnInfo->getColumn());
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
        if ($this->disableSafetyChecks) {
            return;
        }

        $columns = $this->getColumnsFromParameters();

        if (count($columns) === 0) {
            throw new PropelException('Fulltext behavior do not have any columns specified');
        }

        $i18nTable = $this->getI18nBehavior() ? $this->getI18nBehavior()->getI18nTable() : null;
        foreach ($columns as $column) {
            if (!$this->isColumnInTable($this->getTable(), $column) && !$this->isColumnInTable($i18nTable, $column)) {
                throw new PropelException('Unknown column ' . $column);
            }
        }
    }

    protected function isColumnInTable(?Table $table, string $column): bool
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

    protected function hasAnyColumns(): bool
    {
        $result = array_filter($this->columnWeightList, function (FulltextColumnInfo $item) {
            return !$item->isDelegatedColumn();
        });

        return count($result) > 0;
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

    protected function addComputeFulltextValues()
    {
        return $this->renderTemplate('computeFulltextValues', [
            'weights' => $this->getColumnsWeights()
        ]);
    }
}