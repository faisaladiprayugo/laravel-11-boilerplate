<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMembers extends Model
{
    protected $table = 'group_members';
    protected $primaryKey = 'group_member_id';
    protected $fillable = ['group_member_id', 'group_id', 'user_id', 'role', 'soft_delete'];
}
