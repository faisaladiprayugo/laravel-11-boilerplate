<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHabits extends Model
{
    protected $table = 'user_habits';
    protected $primaryKey = 'user_habit_id';
    protected $fillable = ['user_habit_id', 'user_id', 'group_id', 'name', 'schedule_type', 'schedule', 'soft_delete'];
}
