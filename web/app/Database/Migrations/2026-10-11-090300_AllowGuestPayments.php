<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Guest checkout: orders could already be placed without an account, but
 * the payment row required a user. Make it optional.
 */
class AllowGuestPayments extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('payments', [
            'user_id' => ['name' => 'user_id', 'type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->db->table('payments')->where('user_id', null)->delete();
        $this->forge->modifyColumn('payments', [
            'user_id' => ['name' => 'user_id', 'type' => 'int', 'constraint' => 11, 'unsigned' => true, 'null' => false],
        ]);
    }
}
