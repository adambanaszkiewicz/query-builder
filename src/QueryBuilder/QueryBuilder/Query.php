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

class Query
{
    protected $sql;

    protected $bindings = array();

    protected $pdo;

    public function __construct($sql, array $bindings, PDO $pdo)
    {
        $this->sql      = (string) trim($sql);
        $this->bindings = $bindings;
        $this->pdo      = $pdo;
    }

    public function __toString()
    {
        return $this->getRawSql();
    }

    public function toString()
    {
        return $this->getRawSql();
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function getRawSql()
    {
        return $this->interpolateQuery($this->sql, $this->bindings);
    }

    /**
     * See: http://stackoverflow.com/a/1376838/656489
     */
    protected function interpolateQuery($query, $params)
    {
        $keys = array();
        $values = $params;

        # build a regular expression for each parameter
        foreach($params as $key => $value)
        {
            if(is_string($key))
                $keys[] = '/:'.$key.'/';
            else
                $keys[] = '/[?]/';

            if(is_string($value))
                $values[$key] = $this->pdo->quote($value);

            if(is_array($value))
                $values[$key] = implode(',', $this->pdo->quote($value));

            if(is_null($value))
                $values[$key] = 'NULL';
        }

        return preg_replace($keys, $values, $query, 1, $count);
    }
}
