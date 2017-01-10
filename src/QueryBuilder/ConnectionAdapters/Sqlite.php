<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\ConnectionAdapters;

class Sqlite extends BaseAdapter
{
    protected $driverName = 'sqlite';

    public function doConnect($config)
    {
        return new PDO('sqlite:'.$this->getConfigValue('database'), null, null, $config['options']);
    }
}
