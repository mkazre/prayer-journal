<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpdatedAtToPayments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payments', [
            'updated_at' => ['type' => 'datetime', 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('payments', 'updated_at');
    }
}
