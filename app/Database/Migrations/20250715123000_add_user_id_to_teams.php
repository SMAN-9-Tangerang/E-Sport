<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserIdToTeams extends Migration
{
    public function up()
    {
        $fields = [
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ];
        $this->forge->addColumn('teams', $fields);

        // Optionally add foreign key constraint if users table exists
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
    }

    public function down()
    {
        $this->forge->dropColumn('teams', 'user_id');
    }
}
