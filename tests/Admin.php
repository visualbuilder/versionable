<?php

namespace Tests;

use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends \Illuminate\Foundation\Auth\User
{
    use SoftDeletes;

    protected $fillable = ['name', 'email'];
}
