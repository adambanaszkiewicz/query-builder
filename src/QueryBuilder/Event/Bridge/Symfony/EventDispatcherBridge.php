<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\Event\Bridge\Symfony;

use Symfony\Component\EventDispatcher\EventDispatcher;

class SymfonyEventDispatcherBridge extends EventDispatcher implements EventDispatcherInterface
{
    protected $symfonyEventDispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->symfonyEventDispatcher = $dispatcher;
    }

    public function registerEventListener($event, $table, \Closure $listener)
    {
        $this->symfonyEventDispatcher->addListener($this->createEventName($event, $table), $listener);
    }

    public function removeEventListeners($event, $table)
    {

    }

    public function dispatch($event, $table, array $params = [])
    {
        $event = new SymfonyEvent($event, $params);

        $results = $this->symfonyEventDispatcher->dispatch($this->createEventName($event, $table), $event);

        return $results;
    }

    public function createEventName($event, $table = ':all')
    {
        $table = $this->sanitizeString($table);
        $event = $this->sanitizeString($event);

        return 'query_builder.'.$event.'.'.$table;
    }

    public function sanitizeString($string)
    {
        return preg_replace('/[^a-z0-9\_]/i', '_', $string);
    }
}
