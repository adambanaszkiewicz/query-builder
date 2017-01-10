<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\ConnectionAdapters;

abstract class BaseAdapter
{
    protected $driverName = 'mysql';

    protected $config = [];

    public function connect($config)
    {
        $this->config = $config;

        return $this->doConnect($config);
    }

    public function getDriverName()
    {
        return $this->driver;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getConfigValue($name, $default = null)
    {
        return isset($this->config[$name]) ? $this->config[$name] : $default;
    }

    abstract protected function doConnect();
}
