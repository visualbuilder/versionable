<?php

namespace Tests;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends \Illuminate\Foundation\Auth\User
{
    use SoftDeletes;

    protected $fillable = ['name', 'email'];
}
