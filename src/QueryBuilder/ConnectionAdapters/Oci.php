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

class Oci extends BaseAdapter
{
    protected $driverName = 'oci';

    protected function doConnect()
    {
        $url = '"oci:dbname='.$this->getConfigValue('database');
        
        if($this->getConfigValue('host'))
            $url .= ';host='.$this->getConfigValue('host');
        
        if($this->getConfigValue('port'))
            $url .= '";port='.$this->getConfigValue('port');

        return new PDO($url, $this->getConfigValue('username'), $this->getConfigValue('password'), $this->getConfigValue('options'));
    }
}
