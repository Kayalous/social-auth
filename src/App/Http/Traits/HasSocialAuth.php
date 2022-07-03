<?php
namespace Kayalous\SocialAuth\App\Http\Traits;
use Kayalous\SocialAuth\App\Http\Models\SocialProvider;

trait HasSocialAuth {
    function socialProviders()
    {
        return $this->hasMany(SocialProvider::class);
    }
}
