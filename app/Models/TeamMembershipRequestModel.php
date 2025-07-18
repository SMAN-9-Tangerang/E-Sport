<?php

namespace App\Models;

use CodeIgniter\Model;

class TeamMembershipRequestModel extends Model
{
    protected $table = 'team_membership_requests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'team_id',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
}
