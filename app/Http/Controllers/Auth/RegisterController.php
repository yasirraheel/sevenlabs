<?php

namespace App\Http\Controllers\Auth;

use Cookie;
use Validator;
use App\Models\Referrals;
use App\Models\User;
use App\Models\Countries;
use App\Models\AdminSettings;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Helper;
use Mail;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AdminSettings $settings)
    {
        $this->middleware('guest');
        $this->settings = $settings::first();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
      $settings = AdminSettings::first();
      $data['_captcha'] = $settings->captcha;

		$messages = array (
			"letters"    => trans('validation.letters'),
      'g-recaptcha-response.required_if' => trans('misc.captcha_error_required'),
      'g-recaptcha-response.captcha' => trans('misc.captcha_error'),
      'phone.pakistan_phone' => trans('auth.phone_pakistan'),
        );

		 Validator::extend('ascii_only', function($attribute, $value, $parameters){
    		return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		// Validate if have one letter
	Validator::extend('letters', function($attribute, $value, $parameters){
    	return preg_match('/[a-zA-Z0-9]/', $value);
	});

		// Validate Pakistan phone number
	Validator::extend('pakistan_phone', function($attribute, $value, $parameters){
    	// Remove any non-digit characters
    	$phone = preg_replace('/[^0-9]/', '', $value);

    	// Check if it starts with +92 or 92
    	if (strpos($value, '+92') === 0) {
    		$phone = substr($phone, 2); // Remove 92
    	} elseif (strpos($value, '92') === 0) {
    		$phone = substr($phone, 2); // Remove 92
    	}

    	// Pakistan mobile numbers are 11 digits starting with 03
    	// Valid formats: 03XXXXXXXXX (11 digits starting with 03)
    	// The number +923006859611 becomes 3006859611 after removing 92
    	// We need to check if it's 10 digits starting with 3 (after removing 92)
    	return preg_match('/^3[0-9]{9}$/', $phone) && strlen($phone) === 10;
	});

        return Validator::make($data, [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|pakistan_phone|unique:users',
            'city' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'agree_gdpr' => 'required',
            'g-recaptcha-response' => 'required_if:_captcha,==,on|captcha'
        ],$messages);
    }

	public function showRegistrationForm()
  {
     	$settings = AdminSettings::first();

		if ($settings->registration_active == '1')	{
			return view('auth.register');
		} else {
			return redirect('/');
		}
  }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
    	$settings    = AdminSettings::first();

		// Verify Settings Admin
		if( $settings->email_verification == '1' ) {

			$confirmation_code = str_random(100);
			$status = 'pending';

			//send verification mail to user
		 $_username      = $data['username'];
	   $_email_user    = $data['email'];
		 $_title_site    = $settings->title;
		 $_email_noreply = $settings->email_no_reply;

		 Mail::send('emails.verify', ['confirmation_code' => $confirmation_code],
		 function($message) use (
				 $_username,
				 $_email_user,
				 $_title_site,
				 $_email_noreply
		 ) {
          $message->from($_email_noreply, $_title_site);
          $message->subject(trans('users.title_email_verify'));
          $message->to($_email_user,$_username);
            });

		} else {
			$confirmation_code = '';
			$status            = 'active';
		}

		$token   = str_random(75);

    if ($settings->who_can_upload == 'all') {
      $authorized_to_upload = 'yes';
    } else {
      $authorized_to_upload = 'no';
    }

    // Get user country
    $country = Countries::whereCountryCode(Helper::userCountry())->first();

		// Format phone number properly
		$phone = $data['phone'];
		$phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-digits

		// If it starts with 92, remove it
		if (strpos($phone, '92') === 0) {
			$phone = substr($phone, 2);
		}

		// Add +92 prefix for storage
		$formattedPhone = '+92' . $phone;

		$user = User::create([
			'username'        => $data['full_name'], // Use full_name as username
			'name'            => $data['full_name'],
			'full_name'       => $data['full_name'],
			'phone'           => $formattedPhone,
			'city'            => $data['city'],
      'countries_id'    => $country->id ?? '',
			'password'        => bcrypt($data['password']),
			'email'           => strtolower($data['email']),
			'avatar'          => $settings->avatar,
			'cover'           => $settings->cover,
			'status'          => $status,
			'type_account'    => '1',
      'account_no'      => '',
			'activation_code' => $confirmation_code,
      'oauth_uid'       => '',
      'oauth_provider'  => '',
			'token'           => $token,
      'authorized_to_upload' => $authorized_to_upload,
      'ip'               => request()->ip(),
      'credits'          => $settings->signup_bonus_credits ?? 100 // Assign signup bonus credits
		]);

		return $user;
  }// create

  /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $settings = AdminSettings::first();
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));

        // Check Referral
        if ($settings->referral_system == 'on') {

          $referredBy = User::find(Cookie::get('referred'));

          if ($referredBy) {
            Referrals::create([
              'user_id' => $user->id,
              'referred_by' => $referredBy->id,
            ]);
          }
        }

        // Verify Settings Admin
    		if ($settings->email_verification) {

          return redirect('register')
                ->withStatus(trans('auth.check_account'));

        } else {
            $this->guard()->login($user);
            return $this->registered($request, $user)
                  ?: redirect($this->redirectPath());
        }

    }// register
}
