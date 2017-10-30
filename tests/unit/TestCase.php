<?php

namespace Requtize;

use PDO;
use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\QueryBuilder\QueryBuilderFactory;
use Requtize\QueryBuilder\ConnectionAdapters\PdoBridge;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $pdo;
    protected $qb;
    protected $qbPrefixed;

    public function setUp()
    {
        $this->pdo = $this->createPDO();

        $this->qb = $this->createQueryBuilder($this->createConnection($this->pdo));

        $this->qbPrefixed = $this->createQueryBuilder($this->createConnection($this->pdo, [
            'prefix' => 'prefix_'
        ]));
    }

    public function tearDown()
    {
        //Mockery::close();
    }

    public function createPDO()
    {
        return new PDO('sqlite::memory:');
    }

    public function createConnection(PDO $pdo, array $options = [])
    {
        return new Connection(new PdoBridge($pdo), $options);
    }

    public function createQueryBuilder(Connection $conn)
    {
        return new QueryBuilderFactory($conn);
    }

    public function assertSamePrefixedAndNot(\Closure $builder, $query)
    {
        $this->assertSame(
            (string) $builder($this->qb)->getQuery(),
            str_replace('__PREFIX__', '', $query)
        );

        $this->assertSame(
            (string) $builder($this->qbPrefixed)->getQuery(),
            str_replace('__PREFIX__', 'prefix_', $query)
        );
    }
}
