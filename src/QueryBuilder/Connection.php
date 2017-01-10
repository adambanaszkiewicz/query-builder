<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder;

use PDO;
use Exception;
use Requtize\QueryBuilder\Schema\Schema;

class Connection
{
    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \PDO
     */
    protected $pdo;

    protected $schema;

    protected $name = 'default';

    protected $availableDatabases = [
        'mysql',
        'pgsql',
        'mssql',
        'sqlite',
        'cubrid',
        'oci'
    ];

    public function __construct($adapter, $parameters = [])
    {
        $this->setAdapter($adapter)
            ->setParameters($parameters)
            ->connect();

        $this->createSchemaObject();
    }

    protected function connect()
    {
        $this->setPdo($this->createAdapterObject()->connect($this->parameters));
    }

    protected function createAdapterObject()
    {
        if(is_object($this->adapter))
        {
            return $this->adapter;
        }
        elseif(is_string($this->adapter) && in_array($this->adapter, $this->availableDatabases))
        {
            $classname = '\\Requtize\\QueryBuilder\\ConnectionAdapters\\'.ucfirst(strtolower($this->adapter));

            return new $classname;
        }
        elseif(class_exists($this->adapter))
        {
            return new $this->adapter;
        }

        throw new Exception('Given adapter must be one of predefined adapters\' types or a valid adapter class.');
    }

    protected function createSchemaObject()
    {
        $classname = '\\Requtize\\QueryBuilder\\Schema\\'.ucfirst(strtolower($this->adapter->getDriverName()));

        $this->setSchema(new $classname);
    }

    /**
     * @param \PDO $pdo
     *
     * @return $this
     */
    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;

        return $this;
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @param $adapter
     *
     * @return $this
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function setParameters($parameters)
    {
        if(is_array($parameters))
            $this->parameters = new Parameters($parameters);
        elseif($parameters instanceof Parameters)
            $this->parameters = $parameters;
        else
            throw new Exception('Adapter parameters must be an array of Parameters instance.');

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;

        return $this;
    }
}
