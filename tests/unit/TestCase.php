<?php

namespace Requtize;

use PDO;
use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\QueryBuilder\QueryBuilderFactory;
use Requtize\QueryBuilder\ConnectionAdapters\PdoBridge;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $pdo;
    protected $conn;
    protected $qb;

    public function setUp()
    {
        $this->createPDO();
        $this->createConnection();
        $this->createQueryBuilder();
    }

    public function tearDown()
    {
        //Mockery::close();
    }

    public function createPDO()
    {
        $this->pdo = new PDO('sqlite::memory:');
    }

    public function createConnection()
    {
        $this->conn = new Connection(new PdoBridge($this->pdo));
    }

    public function createQueryBuilder()
    {
        $this->qb = new QueryBuilderFactory($this->conn);
    }
}
