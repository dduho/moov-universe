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
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['role'];

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

    public function assignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function validatedTasks()
    {
        return $this->hasMany(Task::class, 'validated_by');
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDealerOwner()
    {
        return $this->hasRole('dealer_owner');
    }

    public function isDealerAgent()
    {
        return $this->hasRole('dealer_agent');
    }

    public function isCommercial()
    {
        return $this->hasRole('dealer_agent');
    }

    public function isDealer()
    {
        return $this->isDealerOwner() || $this->isDealerAgent();
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

        // Dealers (owner and agent) can only access their own organization
        return $this->organization_id == $organizationId;
    }

    /**
     * Check if user can access a specific point of sale
     */
    public function canAccessPointOfSale($pointOfSale)
    {
        // Admins can access all
        if ($this->isAdmin()) {
            return true;
        }

        // Dealer owners can access all PDV in their organization
        if ($this->isDealerOwner()) {
            return $pointOfSale->organization_id == $this->organization_id;
        }

        // Commercials can access PDV they created OR PDV with tasks assigned to them
        if ($this->isCommercial()) {
            return $pointOfSale->created_by == $this->id || 
                   $pointOfSale->tasks()->where('assigned_to', $this->id)->exists();
        }

        // Dealer agents can only access PDV they created
        if ($this->isDealerAgent()) {
            return $pointOfSale->created_by == $this->id;
        }

        return false;
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
