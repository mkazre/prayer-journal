<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreExtrasTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'type'       => ['type' => 'varchar', 'constraint' => 60],
            'body'       => ['type' => 'text'],
            'read_at'    => ['type' => 'datetime', 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('notifications');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'disk_path'   => ['type' => 'varchar', 'constraint' => 255],
            'original_name' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'mime_type'   => ['type' => 'varchar', 'constraint' => 100, 'null' => true],
            'size_bytes'  => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('media');

        // Background job queue processed via `php spark jobs:work` on a cron/task scheduler.
        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'handler'     => ['type' => 'varchar', 'constraint' => 150],
            'payload'     => ['type' => 'text', 'null' => true],
            'status'      => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'attempts'    => ['type' => 'int', 'constraint' => 5, 'default' => 0],
            'run_at'      => ['type' => 'datetime', 'null' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jobs');
    }

    public function down()
    {
        $this->forge->dropTable('jobs', true);
        $this->forge->dropTable('media', true);
        $this->forge->dropTable('notifications', true);
    }
}
