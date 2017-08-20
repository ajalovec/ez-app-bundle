<?php

/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

/**
 * Class TableRowData
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class TableRowData implements TableRowDataInterface, \ArrayAccess
{
    /**
     * @var array
     */
    private $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function __isset($columnId)
    {
        return isset($this->data[$columnId]);
    }

    public function __get($columnId)
    {
        return isset($this->data[$columnId]) ? $this->data[$columnId] : null;
    }

    public function __set($columnId, $value)
    {
        $this->data[$columnId] = $value;
    }

    public function addData(array $data)
    {
        $this->data = array_replace($this->data, $data);
    }

    public function get($columnId, $defaultValue = null)
    {
        return isset($this->data[$columnId]) ? $this->data[$columnId] : $defaultValue;
    }

    public function has($columnId)
    {
        return isset($this->data[$columnId]) && null !== $this->data[$columnId];
    }
}
