<?php

/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Class TableColumn
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class TableColumn
{
    const ALIGN_LEFT   = null;
    const ALIGN_RIGHT  = 1;
    const ALIGN_CENTER = 2;

    /** @var int */
    private $position = 0;

    /** @var int|string */
    private $id;

    /** @var null|string */
    private $name;

    /** @var null|int self::ALIGN_* */
    private $align;

    /**
     * @var null|callable
     */
    private $valueResolver;


    public static function create($id, $name = null, $align = null)
    {
        return new self($id, $name, $align);
    }

    public function __construct($id, $name = null, $align = null)
    {
        $this->id    = $id;
        $this->name  = $name;
        $this->align = $align;
    }

    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return is_null($this->name) ? ucfirst($this->id) : (string)$this->name;
    }

    /**
     * @param null|string|int $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return null|int
     */
    public function getAlign()
    {
        return $this->align;
    }

    /**
     * @param null|int $align
     *
     * @return $this
     */
    public function setAlign($align)
    {
        $this->align = $align;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasValueResolver()
    {
        return isset($this->valueResolver) && $this->valueResolver;
    }

    /**
     * @param callable|null $valueResolver function(TableColumn $column, $value, $row) or
     *
     * @see TableHelper::setDataDecorator if decorator is set than transformation and execution of decorator is left completely up to you
     *
     * @return $this
     */
    public function setValueResolver($valueResolver)
    {
        if (!is_callable($valueResolver) && !is_array($valueResolver) && !is_string($valueResolver)) {
            throw new InvalidArgumentException(sprintf('Argument `$decorator` must be a callable, type `%s` given.', gettype($valueResolver)));
        }

        $this->valueResolver = $valueResolver;

        return $this;
    }

    /**
     * @param TableRowDataInterface $data
     *
     * @return mixed|null
     */
    public function resolveValue(TableRowDataInterface $data)
    {
        $value = $data->get($this->id);

        if ($this->hasValueResolver()) {
            return call_user_func($this->valueResolver, $data, $this->id, $this->getPosition());
        }

        return $value;
    }

//    /**
//     * Get column value from row data
//     *
//     * @param array $data
//     * @param mixed $defaultValue
//     *
//     * @return mixed|null
//     */
//    public function getValue(TableRowDataInterface $data, $defaultValue = null)
//    {
//        return isset($data[$this->id]) ? $data[$this->id] : $defaultValue;
//    }
//
//    /**
//     * Check if column exists in row data
//     *
//     * @param array $data
//     *
//     * @return bool
//     */
//    public function hasValue(array $data)
//    {
//        return isset($data[$this->id]) && null !== $data[$this->id];
//    }
}
