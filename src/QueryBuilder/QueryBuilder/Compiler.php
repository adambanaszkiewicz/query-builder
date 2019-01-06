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
use Requtize\QueryBuilder\Exception\Exception;
use Requtize\QueryBuilder\QueryBuilder\Raw;
use Requtize\QueryBuilder\QueryBuilder\NestedCriteria;
use Requtize\QueryBuilder\Event\EventDispatcherInterface;

class Compiler
{
    protected $connection;
    protected $eventDispatcher;

    protected $tablePrefix;

    public function __construct(Connection $connection, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->connection      = $connection;
        $this->eventDispatcher = $eventDispatcher;

        $this->tablePrefix     = $connection->getParameters()->get('prefix', null);
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        return $this;
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function compile($type, $querySegments, array $data = [])
    {
        $allowedTypes = [ 'select', 'insert', 'insertignore', 'replace', 'delete', 'update', 'criteriaonly' ];

        if(in_array(strtolower($type), $allowedTypes) === false)
            throw new Exception($type.' is not a known type.');

        return $this->{$type}($querySegments, $data);
    }

    public function select($querySegments, array $data)
    {
        if(isset($querySegments['selects']) === false)
            $querySegments['selects'][] = '*';

        list($wheres, $whereBindings) = $this->buildCriteriaOfType($querySegments, 'wheres', 'WHERE');

        if(isset($querySegments['groupBy']) && $groupBy = $this->arrayToString($querySegments['groupBy'], ', ', 'column'))
            $groupBy = 'GROUP BY '.$groupBy;
        else
            $groupBy = '';

        if(isset($querySegments['orderBy']) && is_array($querySegments['orderBy']))
        {
            foreach($querySegments['orderBy'] as $orderBy)
                $orderBy .= $this->quoteTable($orderBy['field']).' '.$orderBy['type'].', ';

            if($orderBy = trim($orderBy, ', '))
                $orderBy = 'ORDER BY '.$orderBy;
        }
        else
        {
            $orderBy = '';
        }

        list($havings, $havingBindings) = $this->buildCriteriaOfType($querySegments, 'havings', 'HAVING');

        $segmentsToBuild = [
            'SELECT'.(isset($querySegments['distinct']) ? ' DISTINCT' : ''),
            $this->arrayToString($querySegments['selects'], ', ', 'column')
        ];

        if(isset($querySegments['tables']))
        {
            $tables = $this->arrayToString($querySegments['tables'], ', ', 'table');

            if($tables)
            {
                $segmentsToBuild[] = 'FROM';
                $segmentsToBuild[] = $tables;
            }
        }

        $segmentsToBuild[] = $this->compileJoin($querySegments);
        $segmentsToBuild[] = $wheres;
        $segmentsToBuild[] = $groupBy;
        $segmentsToBuild[] = $havings;
        $segmentsToBuild[] = $orderBy;
        $segmentsToBuild[] = isset($querySegments['limit'])  ? 'LIMIT '.$querySegments['limit']   : '';
        $segmentsToBuild[] = isset($querySegments['offset']) ? 'OFFSET '.$querySegments['offset'] : '';

        return [
            'sql'      => $this->buildQuerySegment($segmentsToBuild),
            'bindings' => array_merge(
                $whereBindings,
                $havingBindings
            )
        ];
    }

    public function criteriaOnly($querySegments, $binds = true)
    {
        $sql = '';
        $bindings = [];

        if(isset($querySegments['criteria']) === false)
        {
            return [
                'sql'      => $sql,
                'bindings' => $bindings
            ];
        }
        else
        {
            list($sql, $bindings) = $this->buildCriteria($querySegments['criteria'], $binds);

            return [
                'sql'      => $sql,
                'bindings' => $bindings
            ];
        }
    }

    private function doInsert($querySegments, array $data, $type)
    {
        if(! isset($querySegments['tables']))
            throw new Exception('No table given.');

        $table = end($querySegments['tables']);

        $bindings = [];
        $keys     = [];
        $values   = [];

        foreach($data as $key => $value)
        {
            $keys[] = $key;

            if($value instanceof Raw)
            {
                $values[] = (string) $value;
            }
            else
            {
                $values[] = ':'.$key;
                $bindings[':'.$key] = $value;
            }        }

        $segmentsToBuild = [
            $type.' INTO',
            $this->quoteTable($table),
            '('.$this->arrayToString($keys, ', ', 'column').')',
            'VALUES',
            '('.$this->arrayToString($values, ', ', null).')',
        ];

        if(isset($querySegments['onduplicate']))
        {
            if(count($querySegments['onduplicate']) < 1)
                throw new Exception('No data given.');

            list($updateStatement, $updateBindings) = $this->getUpdateStatement($querySegments['onduplicate']);

            $segmentsToBuild[] = 'ON DUPLICATE KEY UPDATE '.$updateStatement;

            $bindings = array_merge($bindings, $updateBindings);
        }

        return [
            'sql'      => $this->buildQuerySegment($segmentsToBuild),
            'bindings' => $bindings
        ];
    }

    public function insert($querySegments, array $data)
    {
        return $this->doInsert($querySegments, $data, 'INSERT');
    }

    public function insertIgnore($querySegments, array $data)
    {
        return $this->doInsert($querySegments, $data, 'INSERT IGNORE');
    }

    public function replace($querySegments, array $data)
    {
        return $this->doInsert($querySegments, $data, 'REPLACE');
    }

    public function update($querySegments, array $data)
    {
        if(isset($querySegments['tables']) === false)
            throw new Exception('No table given.');
        elseif (count($data) < 1)
            throw new Exception('No data given.');

        $table = end($querySegments['tables']);

        list($updates, $bindings) = $this->getUpdateStatement($data);

        list($wheres, $whereBindings) = $this->buildCriteriaOfType($querySegments, 'wheres', 'WHERE');

        $limit = isset($querySegments['limit']) ? 'LIMIT '.$querySegments['limit'] : '';

        $segmentsToBuild = [
            'UPDATE',
            $this->quoteTable($table),
            'SET '.$updates,
            $wheres,
            $limit
        ];

        return [
            'sql'      => $this->buildQuerySegment($segmentsToBuild),
            'bindings' => array_merge($bindings, $whereBindings)
        ];
    }

    public function delete($querySegments)
    {
        if(isset($querySegments['tables']) === false)
            throw new Exception('No table given.');

        $table = end($querySegments['tables']);

        list($wheres, $whereBindings) = $this->buildCriteriaOfType($querySegments, 'wheres', 'WHERE');

        $limit = isset($querySegments['limit']) ? 'LIMIT '.$querySegments['limit'] : '';

        $segmentsToBuild = [ 'DELETE FROM', $this->quoteTable($table), $wheres ];

        return [
            'sql'      => $this->buildQuerySegment($segmentsToBuild),
            'bindings' => $whereBindings
        ];
    }

    public function quoteTable($value)
    {
        if($value instanceof Raw)
            return (string) $value;
        elseif($value instanceof \Closure)
            return $value;

        if(strpos($value, '.'))
        {
            $segments = explode('.', $value, 2);
            $segments[0] = $this->quoteTableName($segments[0]);
            $segments[1] = $this->quoteColumnName($segments[1]);

            return implode('.', $segments);
        }
        else
        {
            return $this->quoteTableName($value);
        }
    }

    public function quoteTableName($value)
    {
        return $this->connection->getSchema()->quoteTableName($value);
    }

    public function quoteColumnName($value)
    {
        return $this->connection->getSchema()->quoteColumnName($value);
    }

    public function quote($value)
    {
        if($value instanceof Raw)
            return (string) $value;
        elseif($value instanceof \Closure)
            return $value;

        if(strpos($value, '.'))
        {
            $segments = [];

            foreach(explode('.', $value, 2) as $val)
                $segments[] = $this->quoteSingle($val);

            return implode('.', $segments);
        }
        else
        {
            return $this->quoteSingle($value);
        }
    }

    public function quoteSingle($value)
    {
        return is_int($value) ? $value : $this->connection->getPdo()->quote($value);
    }

    public function addTablePrefix($value)
    {
        if(is_null($this->tablePrefix))
            return $value;

        if(is_string($value) === false)
            return $value;

        return $this->tablePrefix.$value;
    }

    protected function buildCriteriaOfType($querySegments, $key, $type, $bindValues = true)
    {
        $criteria = '';
        $bindings = [];

        if(isset($querySegments[$key]))
        {
            // Get the generic/adapter agnostic criteria string from parent
            list($criteria, $bindings) = $this->buildCriteria($querySegments[$key], $bindValues);

            if($criteria)
                $criteria = $type.' '.$criteria;
        }

        return [ $criteria, $bindings ];
    }

    protected function compileJoin($querySegments)
    {
        $sql = '';

        if(isset($querySegments['joins']) === false || is_array($querySegments['joins']) === false)
            return $sql;

        foreach($querySegments['joins'] as $joinArr)
        {
            if(is_array($joinArr['table']))
                $table = $this->quoteTable($joinArr['table'][0]).' AS '.$this->quoteTable($joinArr['table'][1]);
            else
                $table = $joinArr['table'] instanceof Raw ? (string) $joinArr['table'] : $this->quoteTable($joinArr['table']);

            $joinBuilder = $joinArr['joinBuilder'];

            $sqlArr = [
                $sql,
                strtoupper($joinArr['type']),
                'JOIN',
                $table,
                'ON',
                $joinBuilder->getQuery('criteriaOnly')->getSql()
            ];

            $sql = $this->buildQuerySegment($sqlArr);
        }

        return $sql;
    }

    protected function getUpdateStatement($data)
    {
        $bindings = [];
        $segment  = '';

        foreach($data as $key => $value)
        {
            if($value instanceof Raw)
            {
                $segment .= $this->quoteColumnName($key).' = '.$value.', ';
            }
            else
            {
                $segment .= $this->quoteColumnName($key).' = '.$key.' , ';
                $bindings[] = $value;
            }
        }

        return [ trim($segment, ', '), $bindings ];
    }

    protected function arrayToString(array $data, $glue, $quote = 'value')
    {
        $elements = [];

        foreach($data as $key => $val)
        {
            if(is_int($val) === false)
            {
                if($quote === 'table' || $quote === 'column')
                    $val = $this->quoteTable($val);
                else if($quote === 'value')
                    $val = $this->quote($val);
            }

            $elements[] = $val;
        }

        return implode($glue, $elements);
    }

    protected function buildQuerySegment(array $data)
    {
        $string = '';

        foreach($data as $val)
        {
            $value = trim($val);

            if($value)
            {
                $string = trim($string).' '.$value;
            }
        }

        return $string;
    }

    protected function buildCriteria($querySegments, $bindValues = true)
    {
        $criteria = '';
        $bindings = [];

        foreach($querySegments as $segment)
        {
            $key   = is_object($segment['key']) ? $segment['key'] : $this->quoteTable($segment['key']);
            $value = $segment['value'];

            if(is_null($value) && $key instanceof \Closure)
            {
                $nestedCriteria = new NestedCriteria($this->connection, $this->eventDispatcher);
                // Call the closure with our new nestedCriteria object
                $key($nestedCriteria);
                // Get the criteria only query from the nestedCriteria object
                $queryObject = $nestedCriteria->getQuery('criteriaOnly');
                // Merge the bindings we get from nestedCriteria object
                $bindings = array_merge($bindings, $queryObject->getBindings());
                // Append the sql we get from the nestedCriteria object
                $criteria .= $segment['joiner'].' ('.$queryObject->getSql().') ';
            }
            elseif(is_array($value))
            {
                // where_in or between like query
                $criteria .= $segment['joiner'].' '.$key.' '.$segment['operator'];

                switch ($segment['operator'])
                {
                    case 'BETWEEN':
                        $bindings = array_merge($bindings, $segment['value']);
                        $criteria .= ' ? AND ? ';

                        break;
                    default:
                        $placeholders = [];

                        foreach($segment['value'] as $subValue)
                        {
                            $placeholders[] = '?';
                            $bindings[] = $subValue;
                        }

                        $criteria .= ' ('.implode(', ', $placeholders).') ';

                        break;
                }
            }
            elseif($value instanceof Raw)
            {
                $criteria .= $segment['joiner'].' '.$key.' '.$segment['operator'].' '.$value.' ';
            }
            else
            {
                if(! $bindValues)
                {
                    $value = is_null($value) ? $value : $this->quote($value);
                    $criteria .= $segment['joiner'].' '.$key.' '.$segment['operator'].' '.$value.' ';
                }
                elseif($segment['key'] instanceof Raw)
                {
                    if($value === null)
                    {
                        $criteria .= $segment['joiner'].' '.$key.' ';
                        $bindings = array_merge($bindings, $segment['key']->getBindings());
                    }
                    else
                    {
                        $criteria .= $segment['joiner'].' '.$key.' '.$segment['operator'].' '.$value.' ';
                    }
                }
                else
                {
                    $valuePlaceholder = '?';
                    $bindings[] = $value;
                    $criteria .= $segment['joiner'].' '.$key.' '.$segment['operator'].' '.$valuePlaceholder.' ';
                }
            }
        }

        return [
            // Clear all white spaces, ands and ors from beginning and white spaces from both ends
            trim(preg_replace("/^(AND|OR)?/i", '', $criteria)),
            $bindings
        ];
    }
}
