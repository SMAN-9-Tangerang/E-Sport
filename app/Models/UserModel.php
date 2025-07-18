<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'username',
        'password_hash',
        'role',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;

    /**
     * Verify user credentials.
     *
     * @param string $username
     * @param string $password
     * @return array|null User data if valid, null otherwise
     */
    public function verifyUser(string $username, string $password)
    {
        $user = $this->where('username', $username)->first();
        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }
}
