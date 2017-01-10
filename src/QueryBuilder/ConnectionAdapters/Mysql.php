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

class Mysql extends BaseAdapter
{
    protected $driverName = 'mysql';

    protected function doConnect()
    {
        $url = 'mysql:dbname='.$this->getConfigValue('database');
        
        if($this->getConfigValue('host'))
            $url .= ';host='.$this->getConfigValue('host');
        
        if($this->getConfigValue('port'))
            $url .= ';port='.$this->getConfigValue('port');

        if($this->getConfigValue('unix_socket'))
            $url .= ';unix_socket='.$this->getConfigValue('unix_socket');

        $connection = new PDO($url, $this->getConfigValue('username'), $this->getConfigValue('password'), $this->getConfigValue('options'));

        if($this->getConfigValue('charset'))
            $connection->prepare('SET NAMES "'.$this->getConfigValue('charset').'"')->execute();

        return $connection;
    }
}
