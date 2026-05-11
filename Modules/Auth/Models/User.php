<?php

namespace Modules\Auth\Models;

 use App\Models\Country;
 use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Auth\database\factories\UserFactory;
use Modules\Auth\enums\KycStatusEnum;
use Modules\Auth\enums\UserStatusEnum;
use Modules\Auth\traits\UserRelationshipTrait;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements JWTSubject,HasMedia,MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, UserRelationshipTrait, InteractsWithMedia;


    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->uuid ??= Str::uuid()->toString();
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'firstname',
        'lastname',
        'username',
        'email',
        'phone_number',
        'dial_code',
        'country_id',
        'password',
        'pin',
        'kyc_status',
        'status',
        'referral_code',
        'referred_by',
        'last_login_at',
        'email_verified_at'
    ];

    protected string $guard = 'api';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'pin' => 'hashed',
        'kyc_status' => KycStatusEnum::class,
        'status' => UserStatusEnum::class,
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'uuid' => $this->uuid,
            'email' => $this->email,
            'status' => $this->status?->value ?? null,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_picture')->singleFile();
    }

    public function getProfilePictureAttribute(): string
    {
        return $this->getFirstMediaUrl('profile_picture');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getCountryNameAttribute(): ?string
    {
        return $this->country?->name;
    }
    protected static function newFactory(): UserFactory|Factory
    {
        return UserFactory::new();
    }
}
