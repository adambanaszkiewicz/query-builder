<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

use PDO;
use PDOException;
use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\Exception\Exception;
use Requtize\QueryBuilder\Exception\QueryExecutionFailException;
use Requtize\QueryBuilder\Event\EventDispatcherInterface;

class QueryBuilder
{
    protected $connection;

    protected $querySegments = [];

    protected $pdo;

    protected $pdoStatement = null;

    protected $compiler;

    protected $fetchMode = [ PDO::FETCH_OBJ ];

    protected $eventDispatcher;

    public function __construct(Connection $connection, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->setConnection($connection);

        if($eventDispatcher)
            $this->setEventDispatcher($eventDispatcher);

        // Set default compiler.
        $this->setCompiler(new Compiler($connection, $eventDispatcher));

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function __clone()
    {
        $this->pdoStatement = null;
    }

    public function setCompiler(Compiler $compiler)
    {
        $this->compiler = $compiler;

        return $this;
    }

    public function getCompiler()
    {
        return $this->compiler;
    }

    public function getQuerySegments()
    {
        return $this->querySegments;
    }

    public function getQuerySegment($key)
    {
        if(isset($this->querySegments[$key]))
            return $this->querySegments[$key];
        else
            return null;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function setFetchMode($mode)
    {
        $this->fetchMode = func_get_args();

        return $this;
    }

    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->pdo        = $connection->getPdo();

        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getSchema()
    {
        return $this->connection->getSchema();
    }

    public function dispatch($event, array $params = [])
    {
        if(! $this->eventDispatcher)
            return;

        $params['query-builder'] = $this;

        return $this->eventDispatcher->dispatch($event, $params);
    }

    public function newQuery(Connection $connection = null)
    {
        if(is_null($connection))
            $connection = $this->connection;

        return new self($connection);
    }

    public function forkQuery()
    {
        return clone $this;
    }

    public function query($sql, $bindings = [])
    {
        $eventResult = $this->dispatch('before-raw-query', [
            'sql'      => $sql,
            'bindings' => $bindings
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $pdoStatement = $this->prepare(
            $sql,
            $bindings
        );

        try
        {
            $pdoStatement->execute();
            $result = call_user_func_array([ $pdoStatement, 'fetchAll' ], $this->fetchMode);
        }
        catch(PDOException $e)
        {
            throw new QueryExecutionFailException($e->getMessage().' during query "'.$sql.'"', $e->getCode(), $e);
        }

        $pdoStatement->closeCursor();
        $pdoStatement = null;
        $this->dispatch('after-raw-query', [
            'sql'      => $sql,
            'bindings' => $bindings,
            'result'   => $result
        ]);

        return $result;
    }

    public function exec($sql, $bindings = [])
    {
        $eventResult = $this->dispatch('before-exec', [
            'sql'      => $sql,
            'bindings' => $bindings
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $pdoStatement = $this->prepare(
            $sql,
            $bindings
        );

        try
        {
            $result = $pdoStatement->execute();
        }
        catch(PDOException $e)
        {
            throw new QueryExecutionFailException($e->getMessage().' during query "'.$sql.'"', $e->getCode(), $e);
        }

        $pdoStatement = null;
        $this->dispatch('after-exec', [
            'sql'      => $sql,
            'bindings' => $bindings,
            'result'   => $result
        ]);

        return $result;
    }

    public function raw($value, $bindings = [])
    {
        return new Raw($value, $bindings);
    }

    public function getQuery($type = 'select', array $parameters = [])
    {
        $result = $this->compiler->compile($type, $this->querySegments, $parameters);

        return new Query($result['sql'], $result['bindings'], $this->pdo);
    }

    public function subQuery(QueryBuilder $queryBuilder, $alias = null)
    {
        $sql = '('.$queryBuilder->getQuery()->getRawSql().')';

        if($alias)
            $sql = $sql.' AS '.$alias;

        return $queryBuilder->raw($sql);
    }

    public function all()
    {
        $query    = $this->getQuery('select');
        $sql      = $query->getSql();
        $bindings = $query->getBindings();

        $eventResult = $this->dispatch('before-select', [
            'query'    => $sql,
            'bindings' => $bindings
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $this->dispatch('before-query', [
            'type'     => 'select',
            'query'    => $sql,
            'bindings' => $bindings
        ]);

        $pdoStatement = $this->prepareAndExecute($sql, $bindings);
        $result = call_user_func_array([ $pdoStatement, 'fetchAll' ], $this->fetchMode);

        $this->dispatch('after-query', [
            'type'     => 'select',
            'query'    => $sql,
            'bindings' => $bindings,
            'result'   => is_array($result) ? count($result) : $result->rowCount()
        ]);

        $this->dispatch('after-select', [
            'query'    => $sql,
            'bindings' => $bindings,
            'result'   => $result
        ]);

        return $result;
    }

    public function allAsObjects($className, $classConstructorArgs = [])
    {
        return $this->asObjects(function () {
            return $this->all();
        }, $className, $classConstructorArgs);
    }

    public function first()
    {
        $this->limit(1);

        $result = $this->all();

        return ! $result ? null : $result[0];
    }

    public function firstAsObject($className, $classConstructorArgs = [])
    {
        return $this->asObjects(function () {
            return $this->first();
        }, $className, $classConstructorArgs);
    }

    private function asObjects(\Closure $callback, $className, $classConstructorArgs)
    {
        $fetchMode = $this->fetchMode;
        $this->setFetchMode(PDO::FETCH_CLASS, $className, $classConstructorArgs);
        $result = $callback();
        call_user_func_array([ $this, 'setFetchMode' ], $fetchMode);
        return $result;
    }

    public function find($value, $field = 'id')
    {
        $this->where($field, '=', $value);

        return $this->first();
    }

    public function findAll($field, $value)
    {
        $this->where($field, '=', $value);

        return $this->all();
    }

    public function from($tables)
    {
        if(is_array($tables) === false)
            $tables = func_get_args();

        $this->addQuerySegment('tables', $this->addTablePrefix($tables, true));

        return $this;
    }

    public function table()
    {
        return call_user_func_array([ $this, 'from' ], func_get_args());
    }

    public function removeTables()
    {
        $this->removeQuerySegment('tables');

        return $this;
    }

    public function select($fields)
    {
        if(is_array($fields) === false)
            $fields = func_get_args();

        $fields = $this->addTablePrefix($fields);
        $this->addQuerySegment('selects', $fields);

        return $this;
    }

    public function removeSelects()
    {
        $this->removeQuerySegment('selects');

        return $this;
    }

    public function selectDistinct($fields)
    {
        $this->select($fields);
        $this->addQuerySegment('distinct', true);

        return $this;
    }

    public function removeSelectDistinct()
    {
        $this->removeQuerySegment('distinct');

        return $this;
    }

    public function count($column = '*')
    {
        $segments = $this->querySegments;

        unset($this->querySegments['orderBy']);
        unset($this->querySegments['limit']);
        unset($this->querySegments['offset']);

        $count = $this->aggregate('COUNT('.$column.')');
        $this->querySegments = $segments;

        return $count;
    }

    public function max($column)
    {
        return $this->aggregate('MAX('.$column.')');
    }

    public function min($column)
    {
        return $this->aggregate('MIN('.$column.')');
    }

    public function sum($column)
    {
        return $this->aggregate('SUM('.$column.')');
    }

    public function avg($column)
    {
        return $this->aggregate('AVG('.$column.')');
    }

    public function insert($data, $table = null)
    {
        if($table)
            $this->table($table);

        return $this->doInsert($data, 'insert');
    }

    public function insertIgnore($data, $table = null)
    {
        if($table)
            $this->table($table);

        return $this->doInsert($data, 'insertignore');
    }

    public function replace($data, $table = null)
    {
        if($table)
            $this->table($table);

        return $this->doInsert($data, 'replace');
    }

    public function update($data, $table = null)
    {
        if($table)
            $this->table($table);

        $query    = $this->getQuery('update', $data);
        $sql      = $query->getSql();
        $bindings = $query->getBindings();

        $eventResult = $this->dispatch('before-update', [
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $this->dispatch('before-query', [
            'type'     => 'update',
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data
        ]);

        $result = $this->prepareAndExecute($sql, $bindings);

        $this->dispatch('after-query', [
            'type'     => 'update',
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data,
            'result'   => $result->rowCount()
        ]);

        $this->dispatch('after-update', [
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data,
            'result'   => $result->rowCount()
        ]);

        return $result->rowCount();
    }

    public function updateOrInsert($data, $table = null)
    {
        if($this->first())
            return $this->update($data, $table);
        else
            return $this->insert($data, $table);
    }

    public function onDuplicateKeyUpdate($data)
    {
        $this->addQuerySegment('onduplicate', $data);

        return $this;
    }

    public function delete($table = null)
    {
        if($table)
            $this->table($table);

        $query    = $this->getQuery('delete');
        $sql      = $query->getSql();
        $bindings = $query->getBindings();

        $eventResult = $this->dispatch('before-delete', [
            'query'    => $sql,
            'bindings' => $bindings,
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $this->dispatch('before-query', [
            'type'     => 'delete',
            'query'    => $sql,
            'bindings' => $bindings,
        ]);

        $result = $this->prepareAndExecute($sql, $bindings);

        $this->dispatch('after-query', [
            'type'     => 'delete',
            'query'    => $sql,
            'bindings' => $bindings,
            'result'   => $result->rowCount()
        ]);

        $this->dispatch('after-delete', [
            'query'    => $sql,
            'bindings' => $bindings,
            'result'   => $result->rowCount()
        ]);

        return $result->rowCount();
    }

    public function groupBy($field)
    {
        $this->addQuerySegment('groupBy', $this->addTablePrefix($field));

        return $this;
    }

    public function removeGroupBy()
    {
        $this->removeQuerySegment('groupBy');

        return $this;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        if(is_array($fields) === false)
            $fields = [ $fields ];

        foreach($fields as $key => $value)
        {
            $field = $key;
            $type  = $value;

            if(is_int($key))
            {
                $field = $value;
                $type  = $defaultDirection;
            }

            if(! $field instanceof Raw)
            {
                $field = $this->addTablePrefix($field);
            }

            $this->querySegments['orderBy'][] = [
                'field' => $field,
                'type'  => $type
            ];
        }

        return $this;
    }

    public function removeOrderBys()
    {
        $this->removeQuerySegment('orderBy');

        return $this;
    }

    public function limit($limit)
    {
        $this->querySegments['limit'] = $limit;

        return $this;
    }

    public function removeLimit()
    {
        $this->removeQuerySegment('limit');

        return $this;
    }

    public function offset($offset)
    {
        $this->querySegments['offset'] = $offset;

        return $this;
    }

    public function removeOffset()
    {
        $this->removeQuerySegment('offset');

        return $this;
    }

    public function having($key, $operator, $value, $joiner = 'AND')
    {
        $this->querySegments['havings'][] = [
            'key'      => $this->addTablePrefix($key),
            'operator' => $operator,
            'value'    => $value,
            'joiner'   => $joiner
        ];

        return $this;
    }

    public function removeHavings()
    {
        $this->removeQuerySegment('havings');

        return $this;
    }

    public function orHaving($key, $operator, $value)
    {
        return $this->having($key, $operator, $value, 'OR');
    }

    public function where($key, $operator = null, $value = null)
    {
        return $this->handleWhere($key, $operator, $value);
    }

    public function orWhere($key, $operator = null, $value = null)
    {
        return $this->handleWhere($key, $operator, $value, 'OR');
    }

    public function whereNot($key, $operator = null, $value = null)
    {
        return $this->handleWhere($key, $operator, $value, 'AND NOT');
    }

    public function orWhereNot($key, $operator = null, $value = null)
    {
        return $this->handleWhere($key, $operator, $value, 'OR NOT');
    }

    public function whereIn($key, $values)
    {
        return $this->handleWhere($key, 'IN', $values, 'AND');
    }

    public function whereNotIn($key, $values)
    {
        return $this->handleWhere($key, 'NOT IN', $values, 'AND');
    }

    public function orWhereIn($key, $values)
    {
        return $this->handleWhere($key, 'IN', $values, 'OR');
    }

    public function orWhereNotIn($key, $values)
    {
        return $this->handleWhere($key, 'NOT IN', $values, 'OR');
    }

    public function whereBetween($key, $valueFrom, $valueTo)
    {
        return $this->handleWhere($key, 'BETWEEN', [ $valueFrom, $valueTo ], 'AND');
    }

    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        return $this->handleWhere($key, 'BETWEEN', [ $valueFrom, $valueTo ], 'OR');
    }

    public function whereNull($key)
    {
        return $this->handleWhereNull($key);
    }

    public function whereNotNull($key)
    {
        return $this->handleWhereNull($key, 'NOT');
    }

    public function orWhereNull($key)
    {
        return $this->handleWhereNull($key, '', 'or');
    }

    public function orWhereNotNull($key)
    {
        return $this->handleWhereNull($key, 'NOT', 'or');
    }

    public function removeWheres()
    {
        $this->removeQuerySegment('wheres');

        return $this;
    }

    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        if(! $key instanceof \Closure)
        {
            $key = function ($joinBuilder) use ($key, $operator, $value) {
                $joinBuilder->on($key, $operator, $value);
            };
        }

        $joinBuilder = new JoinBuilder($this->connection, $this->eventDispatcher);
        $joinBuilder = & $joinBuilder;

        $key($joinBuilder);

        $this->querySegments['joins'][] = [
            'type'        => $type,
            'table'       => $this->addTablePrefix($table, true),
            'joinBuilder' => $joinBuilder
        ];

        return $this;
    }

    public function leftJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'left');
    }

    public function rightJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'right');
    }

    public function innerJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'inner');
    }

    public function removeJoins()
    {
        $this->removeQuerySegment('joins');

        return $this;
    }

    public function beginTransaction()
    {
        $this->pdo->beginTransaction();

        return new Transaction($this->connection, $this->eventDispatcher);
    }

    public function rollbackTransaction()
    {
        $this->pdo->rollback();

        return $this;
    }

    public function commitTransaction()
    {
        $this->pdo->commit();

        return $this;
    }

    public function transaction(\Closure $callback)
    {
        try
        {
            $this->pdo->beginTransaction();

            $transaction = new Transaction($this->connection, $this->eventDispatcher);

            $callback($transaction);

            $this->pdo->commit();

            return $this;
        }
        catch(\Exception $e)
        {
            $this->pdo->rollBack();
            return $this;
        }
    }

    public function prepareAndExecute($sql, $bindings = [])
    {
        $pdoStatement = $this->prepare($sql, $bindings);

        try
        {
            $pdoStatement->execute($bindings);
        }
        catch(PDOException $e)
        {
            throw new QueryExecutionFailException($e->getMessage().' during query "'.$sql.'"', $e->getCode(), $e);
        }

        return $pdoStatement;
    }

    public function prepare($sql, $bindings = [])
    {
        $pdoStatement = $this->pdo->prepare($sql);

        foreach($bindings as $key => $value)
        {
            $pdoStatement->bindValue(
                is_int($key) ? $key + 1 : $key,
                $value,
                is_int($value) || is_bool($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }

        return $pdoStatement;
    }

    public function addTablePrefix($values, $forceAddToAll = false)
    {
        $wasSingleValue = false;

        if(is_array($values) === false)
        {
            $values = [ $values ];
            $wasSingleValue = true;
        }

        $result = [];

        foreach($values as $key => $value)
        {
            if(is_string($value) === false)
            {
                $result[$key] = $value;

                continue;
            }

            $target = & $value;

            if(is_int($key) === false)
                $target = & $key;

            if(strpos($target, '.') === false)
            {
                if($target !== '*' && $forceAddToAll)
                {
                    $target = $this->compiler->addTablePrefix($target);
                }
            }
            else
            {
                $target = $this->compiler->addTablePrefix($target);
            }

            $result[$key] = $value;
        }

        return $wasSingleValue ? $result[0] : $result;
    }

    public function quote($value)
    {
        return $this->compiler->quote($value);
    }

    public function addQuerySegment($key, $value)
    {
        if(is_array($value) === false)
            $value = [ $value ];

        if(isset($this->querySegments[$key]) === false)
            $this->querySegments[$key] = $value;
        else
            $this->querySegments[$key] = array_merge($this->querySegments[$key], $value);
    }

    public function replaceQuerySegment($key, $value)
    {
        $this->removeQuerySegment($key);
        $this->addQuerySegment($key, $value);

        return $this;
    }

    public function removeQuerySegment($key)
    {
        unset($this->querySegments[$key]);

        return $this;
    }

    public function getLastId()
    {
        return $this->pdo->lastInsertId();
    }

    protected function doInsert($data, $type)
    {
        $query    = $this->getQuery($type, $data);
        $sql      = $query->getSql();
        $bindings = $query->getBindings();

        $eventResult = $this->dispatch('before-insert', [
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data
        ]);

        if(is_null($eventResult) === false)
            return $eventResult;

        $this->dispatch('before-query', [
            'type'     => 'insert',
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data
        ]);

        $result = $this->prepareAndExecute($sql, $bindings);
        $return = $result->rowCount() === 1 ? $this->getLastId() : null;

        $this->dispatch('after-query', [
            'type'     => 'insert',
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data,
            'result'   => $result->rowCount()
        ]);

        $this->dispatch('after-insert', [
            'query'    => $sql,
            'bindings' => $bindings,
            'data'     => $data,
            'result'   => $result->rowCount()
        ]);

        return $return;
    }

    protected function aggregate($type)
    {
        $mainSelects = isset($this->querySegments['selects']) ? $this->querySegments['selects'] : null;

        $this->querySegments['selects'] = [ $this->raw($type.' AS field') ];
        $row = $this->all();

        if($mainSelects)
            $this->querySegments['selects'] = $mainSelects;
        else
            unset($this->querySegments['selects']);

        if(is_array($row[0]))
            return (int) $row[0]['field'];
        elseif(is_object($row[0]))
            return (int) $row[0]->field;

        return 0;
    }

    protected function handleWhere($key, $operator = null, $value = null, $joiner = 'AND')
    {
        if($key && $operator && ! $value)
        {
            $value    = $operator;
            $operator = '=';
        }

        $this->querySegments['wheres'][] = [
            'key'      => $this->addTablePrefix($key),
            'operator' => $operator,
            'value'    => $value,
            'joiner'   => $joiner
        ];

        return $this;
    }

    protected function handleWhereNull($key, $prefix = '', $operator = '')
    {
        $key = $this->compiler->quoteColumnName($this->addTablePrefix($key));

        return $this->{$operator.'Where'}($this->raw($key.' IS '.$prefix.' NULL'));
    }
}
