<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\Schema;

class Sqlite extends Schema
{
    public function quoteTableName($name)
    {
        return $this->quoteColumnName($name);
    }

    public function quoteColumnName($name)
    {
        return $name == '*' ? $name : '`'.$name.'`';
    }
}
