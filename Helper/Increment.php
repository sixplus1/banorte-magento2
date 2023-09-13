<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Sixplus1\Banorte\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\App\ResourceConnection;
use \Magento\Framework\App\ObjectManager;

class Increment extends AbstractHelper
{
    /**
     * Constants to be used for DB
     */
    const DB_MAX_PACKET_SIZE = 1048576;

    // Maximal packet length by default in MySQL
    const DB_MAX_PACKET_COEFFICIENT = 0.85;

    /**
     * Custom options tables
     *
     * @var array
     */
    protected $_tables = [
        'sales_order' => null,
    ];



    protected $resourceConnection;

    public function __construct(Context $context, ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
        date_default_timezone_set('America/Mexico_City');
    }

   
    /**
     * Returns next autoincrement value for orderid table
     *
     * @return string
     */
    public function getNextAutoincrementOrder()
    {
        $base = 100000000;
        $salt = 0;
        $prefix = "SP";
        $banorte_key = "";
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sales_order');
        $query = "SELECT MAX(entity_id) AS LastId FROM $table";
        $result = $connection->fetchRow($query);
        if (empty($result['LastId'])) {
             $salt = $base;
             $banorte_key = $prefix."-".$salt;
             $banorte_key = (string)$banorte_key;
             return $banorte_key;
        }
        $salt = $base + $result['LastId'] + 1;
        $banorte_key = $prefix."-".$salt;
        $banorte_key = (string)$banorte_key;
        return $banorte_key;
    }
    


}

