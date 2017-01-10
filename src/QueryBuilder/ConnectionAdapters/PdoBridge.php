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

class PdoBridge extends BaseAdapter
{
    protected $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getDriverName()
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    protected function doConnect()
    {
        return $this->pdo;
    }
}
