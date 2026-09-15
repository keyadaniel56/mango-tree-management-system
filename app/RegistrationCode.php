<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistrationCode extends Model
{
    protected $fillable = ['code', 'role', 'group_id', 'status', 'created_by', 'used_by', 'used_at', 'expires_at'];
}