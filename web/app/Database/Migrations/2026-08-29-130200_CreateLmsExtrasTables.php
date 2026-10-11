<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLmsExtrasTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'certificate_code' => ['type' => 'varchar', 'constraint' => 40],
            'issued_at'      => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['course_id', 'user_id']);
        $this->forge->addUniqueKey('certificate_code');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('certificates');

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'lesson_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'parent_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'body'       => ['type' => 'text'],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('lesson_id');
        $this->forge->addForeignKey('lesson_id', 'lessons', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('parent_id', 'course_qa_threads', 'id', '', 'CASCADE');
        $this->forge->createTable('course_qa_threads');

        $this->forge->addField([
            'id'          => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'       => ['type' => 'varchar', 'constraint' => 200],
            'price_cents' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'currency'    => ['type' => 'varchar', 'constraint' => 3, 'default' => 'USD'],
            'created_by'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at'  => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('course_bundles');

        $this->forge->addField([
            'id'        => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'bundle_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'course_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['bundle_id', 'course_id']);
        $this->forge->addForeignKey('bundle_id', 'course_bundles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->createTable('course_bundle_items');

        $this->forge->addField([
            'id'             => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'course_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'code'           => ['type' => 'varchar', 'constraint' => 40],
            'type'           => ['type' => 'varchar', 'constraint' => 10, 'default' => 'percent'],
            'value'          => ['type' => 'int', 'constraint' => 11],
            'max_redemptions'=> ['type' => 'int', 'constraint' => 11, 'null' => true],
            'times_redeemed' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
            'expires_at'     => ['type' => 'datetime', 'null' => true],
            'created_at'     => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->addForeignKey('course_id', 'courses', 'id', '', 'CASCADE');
        $this->forge->createTable('course_coupons');
    }

    public function down()
    {
        $this->forge->dropTable('course_coupons', true);
        $this->forge->dropTable('course_bundle_items', true);
        $this->forge->dropTable('course_bundles', true);
        $this->forge->dropTable('course_qa_threads', true);
        $this->forge->dropTable('certificates', true);
    }
}
