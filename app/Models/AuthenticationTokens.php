<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthenticationTokens extends Model
{
    protected $table = 'authentication_tokens';
    protected $primaryKey = 'authentication_token_id';
    protected $fillable = ['authentication_token_id', 'user_auth', 'token', 'expired', 'soft_delete'];
}
