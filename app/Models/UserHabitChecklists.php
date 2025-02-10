<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHabitChecklists extends Model
{
    protected $table = 'user_habit_checklists';
    protected $primaryKey = 'user_habit_checklist_id';
    protected $fillable = ['user_habit_checklist_id', 'user_habit_id', 'datetime', 'status', 'soft_delete'];
}
