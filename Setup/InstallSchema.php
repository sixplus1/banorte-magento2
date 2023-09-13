<?php
/**
 * Copyright © Grupo Sonet360 S.A. de C.V. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sixplus1\Banorte\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('sixplus1_banorte_transacciones')) {
            $table = $installer->getConnection()
                ->newTable($installer->getTable('sixplus1_banorte_transacciones'))
                ->addColumn('transaccion_id', Table::TYPE_INTEGER, null, [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary'  => true
                ], 'Transaccion Id')
                ->addColumn('magento_id', Table::TYPE_TEXT, 255, [], 'Magento Id')
                ->addColumn('transaccion', Table::TYPE_TEXT, 255, [], 'Transaccion')
                ->addColumn('transaccion_caduca', Table::TYPE_TEXT, 255, [], 'Transaccion Caduca');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();

    }
}