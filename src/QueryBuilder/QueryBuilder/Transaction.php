<?php
/**
 * Copyright (c) 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/query-builder
 */
namespace Requtize\QueryBuilder\QueryBuilder;

class Transaction extends QueryBuilder
{
    public function commit()
    {
        $this->pdo->commit();

        throw new TransactionHaltException();
    }

    public function rollback()
    {
        $this->pdo->rollBack();

        throw new TransactionHaltException();
    }
}
