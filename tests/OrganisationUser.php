<?php

namespace Tests;

use Illuminate\Database\Eloquent\SoftDeletes;

class OrganisationUser extends \Illuminate\Foundation\Auth\User
{
    use SoftDeletes;

    protected $fillable = ['name', 'email', 'organisation_id'];
}
