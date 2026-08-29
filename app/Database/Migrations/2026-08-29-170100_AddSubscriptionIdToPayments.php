<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubscriptionIdToPayments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payments', [
            'subscription_id' => ['type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'lesson_id'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payments', 'subscription_id');
    }
}
