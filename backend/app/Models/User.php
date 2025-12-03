<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'organization_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdPointOfSales()
    {
        return $this->hasMany(PointOfSale::class, 'created_by');
    }

    public function validatedPointOfSales()
    {
        return $this->hasMany(PointOfSale::class, 'validated_by');
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDealer()
    {
        return $this->hasRole('dealer');
    }

    /**
     * Check if user can access a specific organization's data
     */
    public function canAccessOrganization($organizationId)
    {
        // Admins can access all organizations
        if ($this->isAdmin()) {
            return true;
        }

        // Dealers can only access their own organization
        return $this->organization_id == $organizationId;
    }

    /**
     * Get query scope for user's accessible organizations
     */
    public function getAccessibleOrganizationIds()
    {
        // Admins can access all organizations
        if ($this->isAdmin()) {
            return null; // null means all
        }

        // Dealers can only access their own organization
        return [$this->organization_id];
    }
}
