<?php

namespace Requtize\QueryBuilder\QueryBuilder;

use PDO;
use Requtize\TestCase;
use Requtize\QueryBuilder\Connection;
use Requtize\QueryBuilder\ConnectionAdapters\PdoBridge;

class QueryBuilderTest extends TestCase
{
    public function testSelect()
    {
        /**
         * Select all as one argument.
         */
        $qb = $this->qb->from('table')->select('*');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT * FROM `table`'
        );

        /**
         * Select as one argument.
         */
        $qb = $this->qb->from('table')->select('id');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `id` FROM `table`'
        );

        /**
         * Selects as many arguments.
         */
        $qb = $this->qb->from('table')->select('id', 'name');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `id`, `name` FROM `table`'
        );

        /**
         * Selects as array.
         */
        $qb = $this->qb->from('table')->select([ 'first_name', 'last_name' ]);

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `first_name`, `last_name` FROM `table`'
        );

        /**
         * RAW select columns not quoted.
         */
        $qb = $this->qb->from('table');
        $qb->select($qb->raw('first_name, last_name'));

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT first_name, last_name FROM `table`'
        );

        /**
         * RAW select columns quoted.
         */
        $qb = $this->qb->from('table');
        $qb->select($qb->raw('`first_name`, `last_name`'));

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `first_name`, `last_name` FROM `table`'
        );

        /**
         * Multiple calls and all columns joined.
         */
        $qb = $this->qb->from('table')
            ->select('id')
            ->select([ 'first_name', 'last_name' ])
            ->select($qb->raw('raw'));

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `id`, `first_name`, `last_name`, raw FROM `table`'
        );
    }

    public function testSelectWithTableSpecified()
    {
        $qb = $this->qb->from('table')->select('table.id');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `table`.`id` FROM `table`'
        );
    }

    public function testSelectTableAlias()
    {
        $qb = $this->qb->from('table')->select('table.id');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `table`.`id` FROM `table`'
        );
    }

    public function testTable()
    {
        $qb = $this->qb->select('id')->from('table')->table('asd', '123');

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT `id` FROM `table`, `asd`, `123`'
        );
    }

    public function testWhere()
    {
        $qb = $this->qb->table('table')
            ->select('*')
            ->where('col1', 1)
            ->where('col2', '<', 2)
            ->orWhere('col3', '!=', 3)
            ->whereNot('col4', 4)
            ->orWhereNot('col5', 5);

        $this->assertSame(
            (string) $qb->getQuery(),
            'SELECT * FROM `table` WHERE `col1` = 1 AND `col2` < 2 OR `col3` != 3 AND NOT `col4` = 4 OR NOT `col5` = 5'
        );
    }
}
