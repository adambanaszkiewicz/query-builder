<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\Event\EventDispatcherInterface;

class QueryBuilderFactory
{
    public function __construct(Connection $connection, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->connection      = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __call($name, $args)
    {
        return call_user_func_array([ $this->create(), $name ], $args);
    }

    public function create()
    {
        return new QueryBuilder($this->connection, $this->eventDispatcher);
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }
}
