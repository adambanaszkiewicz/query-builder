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
use Requtize\QueryBuilder\Event\EventDispatcherInterface;

class SymfonyEventDispatcherBridge implements EventDispatcherInterface
{
    protected $symfonyEventDispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->symfonyEventDispatcher = $dispatcher;
    }

    public function registerEventListener($event, \Closure $listener)
    {
        $this->symfonyEventDispatcher->addListener($this->createEventName($event), $listener);
    }

    public function removeEventListeners($event)
    {

    }

    public function dispatch($event, array $params = [])
    {
        $symfonyEvent = new SymfonyEvent($event, $params);

        $this->symfonyEventDispatcher->dispatch($this->createEventName($event), $symfonyEvent);

        return $symfonyEvent->getResult();
    }

    public function createEventName($event)
    {
        $event = $this->sanitizeString($event);

        return 'query_builder.'.$event;
    }

    public function sanitizeString($string)
    {
        return preg_replace('/[^a-z0-9\_]/i', '_', $string);
    }
}
