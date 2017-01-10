<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder;

class Container
{
    /**
     * @var Container
     */
    protected $connections = [];

    public function addConnection($name, $connection)
    {
        $this->connections[$name] = $connection;

        return $this;
    }

    public function getConnection($name)
    {
        return $this->connections[$name];
    }
}
