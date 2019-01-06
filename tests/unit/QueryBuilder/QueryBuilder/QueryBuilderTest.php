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
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')->select('*');
            },
            'SELECT * FROM `__PREFIX__table`'
        );

        /**
         * Select as one argument.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')->select('id');
            },
            'SELECT `id` FROM `__PREFIX__table`'
        );

        /**
         * Selects as many arguments.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')->select('id', 'table.name');
            },
            'SELECT `id`, `__PREFIX__table`.`name` FROM `__PREFIX__table`'
        );

        /**
         * Selects as array.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')->select([ 'first_name', 'table.last_name' ]);
            },
            'SELECT `first_name`, `__PREFIX__table`.`last_name` FROM `__PREFIX__table`'
        );

        /**
         * RAW select columns not quoted.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')
                    ->select($qb->raw('first_name, table.last_name'));
            },
            'SELECT first_name, table.last_name FROM `__PREFIX__table`'
        );

        /**
         * RAW select columns quoted.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')
                    ->select($qb->raw('`first_name`, table.`last_name`, `table`.`last_name`'));
            },
            'SELECT `first_name`, table.`last_name`, `table`.`last_name` FROM `__PREFIX__table`'
        );

        /**
         * Multiple calls and all columns joined.
         */
        $this->assertSamePrefixedAndNot(
            function ($qb) {
                return $qb->from('table')
                    ->select('id')
                    ->select([ 'first_name', 'table.last_name' ])
                    ->select($qb->raw('raw_column'));
            },
            'SELECT `id`, `first_name`, `__PREFIX__table`.`last_name`, raw_column FROM `__PREFIX__table`'
        );
    }

    public function testSelectWithTableSpecified()
    {
        $builder = function ($qb) {
            return $qb
                ->from('table')
                ->select('table.id');
        };

        $this->assertSamePrefixedAndNot(
            $builder,
            'SELECT `__PREFIX__table`.`id` FROM `__PREFIX__table`'
        );
    }

    public function testTable()
    {
        $builder = function ($qb) {
            return $qb
                ->select('id')
                ->select('table.id')
                ->select($qb->raw('prefixed_free.id'))
                ->from('table')
                ->table('asd', '123')
                ->table($qb->raw('prefixed_free'));
        };

        $this->assertSamePrefixedAndNot(
            $builder,
            'SELECT `id`, `__PREFIX__table`.`id`, prefixed_free.id FROM `__PREFIX__table`, `__PREFIX__asd`, `__PREFIX__123`, prefixed_free'
        );
    }

    public function testWhere()
    {
        $builder = function ($qb) {
            return $qb->table('table')
                ->select('*')
                ->where('col1', 1)
                ->where('col2', '<', 2)
                ->orWhere('col3', '!=', 3)
                ->whereNot('col4', 4)
                ->orWhereNot('col5', 5)
                ->where($qb->raw('prefixed_free.col6'), 6)
                ->where($qb->raw('prefixed_free.col7 = 7'))
                ->where($qb->raw('prefixed_free.col8 = :val', [ ':val' => 8 ]));
        };

        $this->assertSamePrefixedAndNot(
            $builder,
            'SELECT * FROM `__PREFIX__table` WHERE `col1` = 1 AND `col2` < 2 OR `col3` != 3 AND NOT `col4` = 4 OR NOT `col5` = 5 AND prefixed_free.col6 = 6 AND prefixed_free.col7 = 7 AND prefixed_free.col8 = :val'
        );
    }

    public function testNestedQuery()
    {
        $builder = function ($qb) {
            $subQuery = $qb
                ->select('name')
                ->from('persons')
                ->where('id', 15);

            $query = $qb
                ->select('*')
                ->select('table.*')
                ->from('table')
                ->select($qb->subQuery($subQuery, 'alias1'));

            return $qb
                ->select('*')
                ->from($qb->subQuery($query, 'alias2'));
        };

        $this->assertSamePrefixedAndNot(
            $builder,
            'SELECT * FROM (SELECT *, `__PREFIX__table`.*, (SELECT `name` FROM `__PREFIX__persons` WHERE `id` = 15) AS alias1 FROM `__PREFIX__table`) AS alias2'
        );
    }
}
