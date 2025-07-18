<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'username' => 'user1',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'User One',
                'nisn' => '1234567890',
                'class' => '10A',
            ],
            [
                'username' => 'user2',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'User Two',
                'nisn' => '1234567891',
                'class' => '10B',
            ],
            [
                'username' => 'user3',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'User Three',
                'nisn' => '1234567892',
                'class' => '10C',
            ],
            [
                'username' => 'user4',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'User Four',
                'nisn' => '1234567893',
                'class' => '10D',
            ],
            [
                'username' => 'user5',
                'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
                'role' => 'user',
                'name' => 'User Five',
                'nisn' => '1234567894',
                'class' => '10E',
            ],
        ];

        foreach ($users as $user) {
            $this->db->table('users')->insert($user);
        }
    }
}
