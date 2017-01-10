<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\ConnectionAdapters;

use PDO;

class Pgsql extends BaseAdapter
{
    protected $driverName = 'pgsql';

    protected function doConnect($config)
    {
        $url = 'pgsql:host='.$this->getConfigValue('host').';dbname='.$this->getConfigValue('database');

        if($this->getConfigValue('port'))
            $url .= ';port='.$this->getConfigValue('port');

        $connection = new PDO($url, $this->getConfigValue('username'), $this->getConfigValue('password'), $this->getConfigValue('options'));

        if($this->getConfigValue('charset'))
            $connection->prepare("SET NAMES '".$this->getConfigValue('charset')."'")->execute();

        if($this->getConfigValue('schema'))
            $connection->prepare("SET search_path TO '".$this->getConfigValue('schema')."'")->execute();

        return $connection;
    }
}
