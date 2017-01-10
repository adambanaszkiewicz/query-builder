<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\Event;

use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\Parameters;

Class EventDispatcher implements EventDispatcherInterface
{
    protected $listeners = [];
    protected $connection;

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function registerEventListener($event, \Closure $listener)
    {
        $this->listeners[$event][] = $listener;

        return $this;
    }

    public function removeEventListeners($event)
    {
        unset($this->listeners[$event]);

        return $this;
    }

    public function dispatch($event, array $params = [])
    {
        if(isset($this->listeners[$event]))
        {
            $parameters = new Parameters($params);

            foreach($this->listeners[$event] as $listener)
            {
                $result = call_user_func_array($listener, [ $parameters ]);

                if($result !== null)
                {
                    return $result;
                }
            }
        }
    }
}
