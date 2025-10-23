<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Visualbuilder\Versionable\Versionable;
use Visualbuilder\Versionable\VersionStrategy;

class Post extends Model
{
    use Versionable;

    protected $fillable = ['title', 'content', 'user_id', 'extends', 'not_versionable_field'];

    protected $versionable = ['title', 'content', 'extends'];

    protected $versionStrategy = VersionStrategy::DIFF;

    protected $casts = [
        'extends' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Post $post) {
            $post->user_id = \auth()->id();
        });
    }

    public function enableForceDeleteVersion()
    {
        $this->forceDeleteVersion = true;
    }

    public function disableForceDeleteVersion()
    {
        $this->forceDeleteVersion = false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
