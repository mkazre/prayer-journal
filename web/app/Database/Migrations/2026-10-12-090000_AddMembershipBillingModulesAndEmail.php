<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * - Pay-per-module: a price on each course module, and per-user unlocks.
 * - Recurring memberships: PayFast subscription token and lifecycle fields.
 * - Outgoing email queue (sent by `php spark app:cron`).
 */
class AddMembershipBillingModulesAndEmail extends Migration
{
    public function up()
    {
        $this->forge->addColumn('course_sections', [
            'price_cents' => ['type' => 'int', 'constraint' => 11, 'default' => 0],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'section_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'section_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('section_id', 'course_sections', 'id', '', 'CASCADE');
        $this->forge->createTable('section_unlocks');

        $this->forge->addColumn('payments', [
            'section_id'  => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'external_id' => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
        ]);

        $this->forge->addColumn('user_subscriptions', [
            'gateway'          => ['type' => 'varchar', 'constraint' => 20, 'null' => true],
            'gateway_token'    => ['type' => 'varchar', 'constraint' => 120, 'null' => true],
            'renews'           => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'last_payment_at'  => ['type' => 'datetime', 'null' => true],
            'cancelled_at'     => ['type' => 'datetime', 'null' => true],
            'reminded_for'     => ['type' => 'datetime', 'null' => true],
            'notice_stage'     => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
            'updated_at'       => ['type' => 'datetime', 'null' => true],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'to_email'   => ['type' => 'varchar', 'constraint' => 190],
            'to_name'    => ['type' => 'varchar', 'constraint' => 190, 'null' => true],
            'template'   => ['type' => 'varchar', 'constraint' => 60],
            'subject'    => ['type' => 'varchar', 'constraint' => 255],
            'html'       => ['type' => 'mediumtext'],
            'text'       => ['type' => 'mediumtext', 'null' => true],
            'status'     => ['type' => 'varchar', 'constraint' => 10, 'default' => 'queued'],
            'attempts'   => ['type' => 'tinyint', 'constraint' => 3, 'default' => 0],
            'last_error' => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'sent_at'    => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'id']);
        $this->forge->createTable('email_queue');
    }

    public function down()
    {
        $this->forge->dropTable('email_queue', true);
        $this->forge->dropColumn('user_subscriptions', ['gateway', 'gateway_token', 'renews', 'last_payment_at', 'cancelled_at', 'reminded_for', 'notice_stage', 'updated_at']);
        $this->forge->dropColumn('payments', ['section_id', 'external_id']);
        $this->forge->dropTable('section_unlocks', true);
        $this->forge->dropColumn('course_sections', ['price_cents']);
    }
}
