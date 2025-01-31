<?php

namespace App\Services;

use Cookie;
use App\Helper;
use App\Models\User;
use App\Models\Countries;
use App\Models\Referrals;
use Illuminate\Support\Str;
use App\Http\Controllers\Traits\Functions;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAccountService
{
  use Functions;

  public function createOrGetUser(ProviderUser $providerUser, $provider)
  {
    $user = User::whereOauthProvider($provider)
      ->whereOauthUid($providerUser->getId())
      ->first();

    if (!$user) {
      //return 'Error! Your email is required, Go to app settings and delete our app and try again';
      if (!$providerUser->getEmail()) {
        return redirect("login")->with(array('login_required' => __('error.error_required_mail')));
        exit;
      }

      //Verify Email user
      $userEmail = User::whereEmail($providerUser->getEmail())->first();

      if ($userEmail) {
        return redirect("login")->with(array('login_required' => __('error.mail_exists')));
        exit;
      }

      $token = Str::random(75);

      $avatar = 'default.jpg';
      $nameAvatar = time() . $providerUser->getId();
      $path = config('path.avatar');

      if (!empty($providerUser->getAvatar())) {

        // Get Avatar Large Facebook
        if ($provider == 'facebook') {
          $avatarUser = str_replace('?type=normal', '?type=large', $providerUser->getAvatar());

          $fileContents = file_get_contents($avatarUser);

          \Storage::put($path . $nameAvatar . '.jpg', $fileContents, 'public');

          $avatar = $nameAvatar . '.jpg';
        }

        // Get Avatar Large Twitter
        if ($provider == 'twitter') {
          $avatarUser = str_replace('_normal', '_200x200', $providerUser->getAvatar());

          $fileContents = file_get_contents($avatarUser);

          \Storage::put($path . $nameAvatar . '.jpg', $fileContents, 'public');

          $avatar = $nameAvatar . '.jpg';
        }

        // Get Avatar Google
        if ($provider == 'google') {
          $avatarUser = str_replace('=s96', '=s200', $providerUser->getAvatar());

          $fileContents = file_get_contents($avatarUser);

          \Storage::put($path . $nameAvatar . '.jpg', $fileContents, 'public');

          $avatar = $nameAvatar . '.jpg';
        }
      } // Empty getAvatar()

      // Get user country
      $country = Countries::whereCountryCode(Helper::userCountry())->first();

      $user = User::create([
        'username'          => Helper::strRandom(),
        'countries_id'      => $country->id ?? '',
        'name'              => $providerUser->getName(),
        'email'             => strtolower($providerUser->getEmail()),
        'password'          => '',
        'avatar'            => $avatar,
        'cover'             => config('settings.cover_default') ?? '',
        'status'            => 'active',
        'role'              => 'normal',
        'permission'        => 'none',
        'confirmation_code' => '',
        'oauth_uid'         => $providerUser->getId(),
        'oauth_provider'    => $provider,
        'token'             => $token,
        'story'             => __('users.story_default'),
        'verified_id'       => config('settings.account_verification') ? 'no' : 'yes',
        'ip'                => request()->ip(),
        'language'          => session('locale'),
        'hide_name'         => 'yes',
        'dark_mode'         => config('settings.theme') == 'light' ? 'off' : 'on',
      ]);

      // Check Referral
      if (config('settings.referral_system') == 'on') {
        $referredBy = User::find(Cookie::get('referred'));

        if ($referredBy) {
          Referrals::create([
            'user_id' => $user->id,
            'referred_by' => $referredBy->id,
          ]);
        }
      }

      // Update Username
      $user->update([
        'username' => Helper::createUsername($user->name, $user->id),
      ]);

      if (config('settings.autofollow_admin')) {
        // Auto-follow Admin
        $this->autoFollowAdmin($user->id);
      }

      // Insert Login Session
      $this->loginSession($user->id);
    } // !$user
    return $user;
  }
}
