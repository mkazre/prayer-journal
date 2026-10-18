<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Digital PDF products can be read online as a flipbook, and the PDF
 * download can be switched off per product.
 */
class AddFlipbookToProducts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('products', [
            'allow_download' => ['type' => 'tinyint', 'constraint' => 1, 'default' => 1],
            'flipbook'       => ['type' => 'tinyint', 'constraint' => 1, 'default' => 0],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('products', ['allow_download', 'flipbook']);
    }
}
