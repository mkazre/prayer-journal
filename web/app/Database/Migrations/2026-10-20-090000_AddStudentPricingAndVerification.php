<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Student pricing: membership plans get an optional student price, each
 * subscription remembers what it actually charges, and verified student
 * status (from SheerID) is recorded with an expiry date.
 */
class AddStudentPricingAndVerification extends Migration
{
    public function up()
    {
        $this->forge->addColumn('subscription_plans', [
            // Price for verified students; null = no student price on this plan.
            'student_price_cents' => ['type' => 'int', 'constraint' => 11, 'null' => true],
        ]);
        $this->forge->addColumn('user_subscriptions', [
            // What PayFast bills each period (standard or student price at signup).
            'amount_cents' => ['type' => 'int', 'constraint' => 11, 'null' => true],
            'is_student'   => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ]);

        $this->forge->addField([
            'id'           => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'int', 'constraint' => 11, 'unsigned' => true],
            'provider'     => ['type' => 'varchar', 'constraint' => 20, 'default' => 'sheerid'],
            'external_id'  => ['type' => 'varchar', 'constraint' => 80],
            // pending, verified, rejected, expired, error
            'status'       => ['type' => 'varchar', 'constraint' => 20, 'default' => 'pending'],
            'institution'  => ['type' => 'varchar', 'constraint' => 200, 'null' => true],
            'segment'      => ['type' => 'varchar', 'constraint' => 60, 'null' => true],
            'detail'       => ['type' => 'varchar', 'constraint' => 255, 'null' => true],
            'verified_at'  => ['type' => 'datetime', 'null' => true],
            'expires_at'   => ['type' => 'datetime', 'null' => true],
            'notified_at'  => ['type' => 'datetime', 'null' => true],
            'created_at'   => ['type' => 'datetime', 'null' => true],
            'updated_at'   => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['provider', 'external_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('student_verifications');
    }

    public function down()
    {
        $this->forge->dropTable('student_verifications', true);
        $this->forge->dropColumn('user_subscriptions', ['amount_cents', 'is_student']);
        $this->forge->dropColumn('subscription_plans', 'student_price_cents');
    }
}
