<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\Event;

interface EventDispatcherInterface
{
    public function registerEventListener($event, \Closure $listener);

    public function removeEventListeners($event);

    public function dispatch($event, array $params = []);
}
