<?php

namespace Kayalous\SocialAuth\App\Http\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialProvider extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'provider'];

    function user()
    {
        return $this->belongsTo(User::class);
    }
}
