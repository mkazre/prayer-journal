<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopInventoryTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'    => ['type' => 'varchar', 'constraint' => 150],
            'address' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('inventory_locations');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'variant_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'location_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'quantity'    => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'updated_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['variant_id', 'location_id']);
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'inventory_locations', 'id', '', 'CASCADE');
        $this->forge->createTable('inventory_levels');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'variant_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'location_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'change'      => ['type' => 'int', 'constraint' => 11],
            'reason'      => ['type' => 'varchar', 'constraint' => 40],
            'reference'   => ['type' => 'varchar', 'constraint' => 60, 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('location_id', 'inventory_locations', 'id', '', 'CASCADE');
        $this->forge->createTable('stock_movements');
    }

    public function down()
    {
        $this->forge->dropTable('stock_movements', true);
        $this->forge->dropTable('inventory_levels', true);
        $this->forge->dropTable('inventory_locations', true);
    }
}
