<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

class Raw
{
    protected $value;

    protected $bindings = [];

    public function __construct($value, array $bindings = [])
    {
        $this->value    = (string) $value;
        $this->bindings = $bindings;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}
