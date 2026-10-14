<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = ['email', 'password_hash', 'username', 'avatar_emoji', 'email_verified_at', 'secret_question', 'secret_answer_hash'];
    protected $hidden = ['password_hash', 'secret_answer_hash'];
    protected $casts = ['email_verified_at' => 'datetime'];

    public function progress(): HasOne { return $this->hasOne(UserProgress::class); }
    public function inventory(): HasMany { return $this->hasMany(UserInventory::class); }
    public function achievements(): HasMany { return $this->hasMany(UserAchievement::class); }
    public function sessions(): HasMany { return $this->hasMany(SessionLog::class); }
    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }
    public function dailyClaims(): HasMany { return $this->hasMany(DailyRewardClaim::class); }
    public function passwordResets(): HasMany { return $this->hasMany(PasswordReset::class); }
}