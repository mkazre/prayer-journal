<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopLogisticsTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'   => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'varchar', 'constraint' => 100],
            'countries' => ['type' => 'text'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('shipping_zones');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'zone_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'name'        => ['type' => 'varchar', 'constraint' => 100],
            'price_cents' => ['type' => 'int', 'constraint' => 11],
            'min_order_cents' => ['type' => 'int', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('zone_id', 'shipping_zones', 'id', '', 'CASCADE');
        $this->forge->createTable('shipping_rates');

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'country'      => ['type' => 'varchar', 'constraint' => 2],
            'region'       => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'rate_percent' => ['type' => 'int', 'constraint' => 5],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('tax_rules');
    }

    public function down()
    {
        $this->forge->dropTable('tax_rules', true);
        $this->forge->dropTable('shipping_rates', true);
        $this->forge->dropTable('shipping_zones', true);
    }
}
