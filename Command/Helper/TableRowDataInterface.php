<?php

/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\Helper;

/**
 * Interface TableRowDataInterface
 * @package   Origammi\Bundle\EzAppBundle\Command\Helper
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface TableRowDataInterface
{
    /**
     * @param string|TableColumn $columnId
     * @param null               $defaultValue
     *
     * @return mixed
     */
    public function get($columnId, $defaultValue = null);

    /**
     * @param string|TableColumn $columnId
     *
     * @return bool
     */
    public function has($columnId);

    /**
     * @param array $data
     *
     * @return self
     */
    public function addData(array $data);
}
