<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupHabits extends Model
{
    protected $table = 'group_habits';
    protected $primaryKey = 'group_habit_id';
    protected $fillable = ['group_habit_id', 'group_id', 'name', 'schedule_type', 'schedule', 'soft_delete'];
}
