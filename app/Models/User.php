<?php

namespace App\Models;

use Laravel\Cashier\Billable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword as ResetPasswordNotification;

class User extends Authenticatable
{
  use Notifiable, Billable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  const CREATED_AT = 'date';
  const UPDATED_AT = null;

  protected $fillable = [
    'username',
    'name',
    'full_name',
    'phone',
    'city',
    'countries_id',
    'email',
    'password',
    'status',
    'type_account',
    'account_no',
    'activation_code',
    'oauth_uid',
    'oauth_provider',
    'token',
    'role',
    'ip',
    'stripe_connect_id',
    'completed_stripe_onboarding',
    'balance'
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'completed_stripe_onboarding' => 'bool',
  ];

  protected $withCount = [
    'newNotifications'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
    'password', 'remember_token',
  ];

  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPasswordNotification($token));
  }

  public function likes()
  {
    return $this->hasMany(Like::class);
  }

  public function comments()
  {
    return $this->hasMany(Comments::class);
  }

  public function following()
  {
    return $this->hasMany(Followers::class, 'follower')->where('status', '1');
  }

  public function followers()
  {
    return $this->hasMany(Followers::class, 'following')->where('status', '1');
  }

  public function notifications()
  {
    return $this->hasMany(Notifications::class, 'destination');
  }

  public function country()
  {
    return $this->belongsTo(Countries::class, 'countries_id')->first();
  }

  public function newNotifications()
  {
    return $this->notifications()->whereStatus('0');
  }

  public function unseenNotifications()
  {
    return $this->new_notifications_count;
  }

  public function followActive($user)
  {
    return $this->following()
      ->where('following', $user)
      ->first();
  }

  /**
   * The tax rates that should apply to the customer's subscriptions.
   *
   * @return array
   */
  public function taxRates()
  {
    $taxRates = [];
    $payment = PaymentGateways::whereName('Stripe')
      ->whereEnabled('1')
      ->where('key_secret', '<>', '')
      ->first();

    if ($payment) {
      $stripe = new \Stripe\StripeClient($payment->key_secret);
      $taxes = $stripe->taxRates->all();

      foreach ($taxes->data as $tax) {
        if (
          $tax->active && $tax->state == $this->getRegion()
          && $tax->country == $this->getCountry()
          || $tax->active
          && $tax->country == $this->getCountry()
          && $tax->state == null
        ) {
          $taxRates[] = $tax->id;
        }
      }
    }

    return $taxRates;
  }

  public function isTaxable()
  {
    return TaxRates::whereStatus('1')
      ->whereIsoState($this->getRegion())
      ->whereCountry($this->getCountry())
      ->orWhere('country', $this->getCountry())
      ->whereNull('iso_state')
      ->whereStatus('1')
      ->get();
  }

  public function taxesPayable()
  {
    return $this->isTaxable()
      ->pluck('id')
      ->implode('_');
  }

  public function getCountry()
  {
    $ip = request()->ip();
    return cache('userCountry-' . $ip) ?? ($this->country()->country_code ?? null);
  }

  public function getRegion()
  {
    $ip = request()->ip();
    return cache('userRegion-' . $ip) ?? null;
  }

  /**
   * User plans
   */
  public function plans()
  {
    return $this->hasMany(Plans::class);
  }

  // Get details plan
  public function plan($interval, $field)
  {
    return $this->plans()
      ->whereInterval($interval)
      ->pluck($field)
      ->first();
  }

  // Get Plan Active
  public function planActive()
  {
    return $this->plans()->whereStatus('1')->first();
  }

  public function referrals()
  {
    return $this->hasMany(Referrals::class, 'referred_by');
  }

  public function referralTransactions()
  {
    return $this->hasMany(ReferralTransactions::class, 'referred_by');
  }

  /**
   * Get the user's Role.
   */
  public function role()
  {
    return $this->belongsTo(RolesAndPermissions::class, 'role')->first();
  }

  /**
   * Get the user's is Super Admin.
   */
  public function isSuperAdmin()
  {
    if ($this->role() && $this->role()->permissions == 'full_access') {
      return $this->id;
    }
    return false;
  }

  /**
   * Get the user's permissions.
   */
  public function hasPermission($section)
  {
    $permissions = explode(',', $this->role()->permissions);

    return in_array($section, $permissions)
      || $this->role()->permissions == 'full_access'
      || $this->role()->permissions == 'limited_access'
      ? true
      : false;
  }

  public function mySubscription()
  {
    return $this->hasMany(Subscriptions::class);
  }

  public function getSubscription()
  {
    return $this->mySubscription()
      ->where('stripe_id', '=', '')
      ->where('ends_at', '>=', now())

      ->orWhere('stripe_id', '<>', '')
      ->where('stripe_status', 'active')
      ->whereUserId($this->id)

      ->orWhere('stripe_id', '<>', '')
      ->where('stripe_status', 'canceled')
      ->where('ends_at', '>=', now())
      ->whereUserId($this->id)
      ->first();
  }

  public function oneSignalDevices()
  {
    return $this->hasMany(UserDevices::class);
  }

  // UserCredits relationship removed - using balance column instead

  public function deposits()
  {
    return $this->hasMany(Deposits::class);
  }
}
