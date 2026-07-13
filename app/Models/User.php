<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Traits\ShopModel;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements IShopModel
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, ShopModel;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'shop_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'shop_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'shop_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shop_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class, 'shop_id');
    }
}
