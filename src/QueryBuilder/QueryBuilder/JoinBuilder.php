<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

class JoinBuilder extends QueryBuilder
{
    public function on($key, $operator, $value = null)
    {
        return $this->joinHandler($key, $operator, $value, 'AND');
    }

    public function orOn($key, $operator, $value = null)
    {
        return $this->joinHandler($key, $operator, $value, 'OR');
    }

    protected function joinHandler($key, $operator = null, $value = null, $joiner = 'AND')
    {
        if($key && $operator && ! $value)
        {
            $value    = $operator;
            $operator = '=';
        }

        $this->querySegments['criteria'][] = [
            'key'      => $this->addTablePrefix($key),
            'operator' => $operator,
            'value'    => $this->addTablePrefix($value),
            'joiner'   => $joiner
        ];

        return $this;
    }
}
