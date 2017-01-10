<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

class NestedCriteria extends QueryBuilder
{
    protected function handleWhere($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $this->querySegments['criteria'][] = [
            'key'      => $this->addTablePrefix($key),
            'operator' => $operator,
            'value'    => $value,
            'joiner'   => $joiner
        ];

        return $this;
    }
}
