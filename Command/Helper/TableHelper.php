<?php

/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TableHelper
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 *
 *
 * $table
 *  ->setColumns([
 *      TableColumn::create('contentType', 'Content type')
 *          ->setAlign(TableColumn::ALIGN_RIGHT)
 *          ->setValueResolver([$this, 'getLocationFormat']),
 *
 *      TableColumn::create('path', null, TableColumn::ALIGN_LEFT),
 *      TableColumn::create('remoteId', null, TableColumn::ALIGN_LEFT),
 *  ]);
 */
class TableHelper
{
    const ALIGN_LEFT   = TableColumn::ALIGN_LEFT;
    const ALIGN_RIGHT  = TableColumn::ALIGN_RIGHT;
    const ALIGN_CENTER = TableColumn::ALIGN_CENTER;

    const STYLE_DEFAULT    = null;
    const STYLE_BORDERLESS = 2;
    const STYLE_SIMPLE     = 3;

    /**
     * @var Table
     */
    private $table;

    /**
     * Table columns.
     *
     * @var TableColumn[]
     */
    private $columns = [];

    /**
     * @var int
     */
    private $count = 0;

    /**
     * @var TableSeparator
     */
    private $separator;

    /**
     * TableHelper constructor.
     *
     * @param int|TableStyle $style self::STYLE_*
     */
    public function __construct($style = self::STYLE_DEFAULT)
    {
        $this->table = new Table(new NullOutput());
        $this->setStyle($style);
    }

    /**
     * @param mixed|TableStyle $style self::STYLE_*
     *
     * @return $this
     * @throws \Exception
     */
    public function setStyle($style = self::STYLE_DEFAULT)
    {
        if ($this->count) {
            throw new \Exception('Can not modify headers when table is not empty.');
        }

        if ($style instanceof TableStyle) {
            $this->table->setStyle($style);
        } else {
            switch ($style) {
                case self::STYLE_BORDERLESS:
                    $this->table->getStyle()
                        ->setHorizontalBorderChar('=')
                        ->setVerticalBorderChar(' ')
                        ->setCrossingChar(' ')
                    ;
                    break;

                case self::STYLE_SIMPLE:
                    $this->table->getStyle()
                        ->setHorizontalBorderChar('-')
                        ->setVerticalBorderChar(' ')
                        ->setCrossingChar(' ')
                        ->setCellHeaderFormat('<fg=yellow>%s</>')
                        //                    ->setCellRowContentFormat(' %s ')
                    ;
                    break;
            }
        }

        return $this;
    }

    /**
     * @param string|int $id
     *
     * @return null|TableColumn
     */
    public function getColumn($id)
    {
        if (isset($this->columns[$id])) {
            return $this->columns[$id];
        }

        return null;
    }

    /**
     * @param TableColumn[] $columns
     *
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = [];

        foreach ($columns as $column) {
            $this->addColumn($column);
        }

        return $this;
    }

    /**
     * @param string|int|TableColumn $id
     * @param null|string            $name
     * @param null|int               $align TableColumn::ALIGN_*
     *
     * @return TableColumn
     * @throws \Exception
     */
    public function addColumn($id, $name = null, $align = null)
    {
        if ($this->count) {
            throw new \Exception('Can not modify headers when table is not empty.');
        }

        if (!$id instanceof TableColumn) {
            if (is_scalar($id)) {
                $id = TableColumn::create($id, $name, $align);
            } else {
                throw new InvalidArgumentException('Argument `$id` must be of type scalar or TableColumn object.');
            }
        }

        $p = new \ReflectionProperty($id, 'position');
        $p->setAccessible(true);
        $p->setValue($id, count($this->columns));

        $this->columns[$id->getId()] = $id;

        return $id;
    }

    /**
     * @param array $rows
     *
     * @return $this
     */
    public function setRows(array $rows)
    {
        $this->count = 0;
        $this->table->setRows([]);

        return $this->addRows($rows);
    }

    public function addRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    /**
     * @param array|TableRowDataInterface $data
     *
     * @return $this
     */
    public function addRow($data)
    {
        $row = [];

        if (is_array($data)) {
            $data = new TableRowData($data);
        } elseif (!$data instanceof TableRowDataInterface) {
            throw new InvalidArgumentException(sprintf('Argument `$row` must be of type array or must implement `%s`. `%s` type given.', TableRowDataInterface::class, gettype($data)));
        }

        foreach ($this->columns as $column) {
            $value = $column->resolveValue($data);

            $row[] = (string)$value;
        }

        $this->table->addRow($row);
        $this->count++;

        return $this;
    }

    /**
     * @return $this
     */
    public function addSeparator()
    {
        if (!$this->count) {
            return $this;
        }

        if (!$this->separator) {
            $this->separator = new TableSeparator([
                'colspan' => count($this->columns),
            ]);
        }

        $this->table->addRow($this->separator);

        return $this;
    }

    /**
     * Renders table to output.
     *
     * Example:
     * +---------------+-----------------------+------------------+
     * | ISBN          | Title                 | Author           |
     * +---------------+-----------------------+------------------+
     * | 99921-58-10-7 | Divine Comedy         | Dante Alighieri  |
     * | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     * | 960-425-059-0 | The Lord of the Rings | J. R. R. Tolkien |
     * +---------------+-----------------------+------------------+
     *
     * @param OutputInterface $output
     */
    public function render(OutputInterface $output)
    {
        foreach (array_keys($this->columns) as $i => $columnId) {
            $align = $this->getAlign($this->columns[$columnId]);

            if ($align instanceof TableStyle) {
                $this->table->setColumnStyle($i, $align);
            }
        }

        $p = new \ReflectionProperty($this->table, 'output');
        $p->setAccessible(true);
        $p->setValue($this->table, $output);

        $this->table
            ->setHeaders($this->columns)
            ->render()
        ;
    }

    private function getAlign(TableColumn $column)
    {
        switch ($column->getAlign()) {
            case TableColumn::ALIGN_RIGHT:
                return (clone $this->table->getStyle())->setPadType(STR_PAD_LEFT);
            case TableColumn::ALIGN_CENTER:
                return (clone $this->table->getStyle())->setPadType(STR_PAD_BOTH);
        }

        return null;
    }
}



