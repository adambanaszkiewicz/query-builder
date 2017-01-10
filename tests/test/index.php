<?php

use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\Parameters;
use Requtize\QueryBuilder\Container as BaseContainer;
use Requtize\QueryBuilder\ConnectionAdapters\PdoBridge;
use Requtize\QueryBuilder\QueryBuilder\QueryBuilderFactory;
use Requtize\QueryBuilder\Event\EventDispatcher;

error_reporting(-1);

include 'autoload.php';

//$container = new BaseContainer;

$connection = new Connection(new PdoBridge(new PDO('mysql:dbname=test;host=localhost', 'root', '')), [
    'prefix' => 'pref_'
]);

//$container->addConnection('default', $connection);

$qb = new QueryBuilderFactory($connection, new EventDispatcher);

$qb->getEventDispatcher()->registerEventListener('before-select', function (Parameters $parameters) {
    //var_dump($parameters->get('query-builder'));
});

$result = $qb->from('user')
    ->select('*')
    ->where('id', '<', 3)
    ->where(function($q) {
        $q->where('id', 'username')
            ->orWhereNotNull('id');
    })
    ->join('user', 'id', 1)
    ->limit(5)
    ->offset(0);

var_dump($result->getQuery()->__toString());
