<?php

namespace App\Http\Controllers;

use Mail;
use App\Helper;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Deposits;
use App\Models\Categories;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\Notifications;
use App\Models\Subcategories;
use App\Models\Subscriptions;
use App\Models\UsersReported;
use App\Models\PaymentGateways;
use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Notifications\DepositVerification;
use Intervention\Image\Laravel\Facades\Image;

class AdminController extends Controller
{

	public function __construct(AdminSettings $settings)
	{
		$this->settings = $settings::first();
	}
	// START
	public function dashboard()
	{
		if (!auth()->user()->hasPermission('dashboard')) {
			return view('admin.unauthorized');
		}

		$totalRevenue = Deposits::whereStatus('approved')->sum('amount');

		// Initialize arrays
		$monthsData = [];
		$revenueSum = [];
		$lastTransactions = [];

		//  Calculate Chart Revenue last 30 days
		for ($i = 0; $i <= 30; ++$i) {

			$date = date('Y-m-d', strtotime('-' . $i . ' day'));

			// Revenue last 30 days
			$deposits = Deposits::whereStatus('approved')->whereDate('date', '=', $date)->sum('amount');

			// Transactions last 30 days
			$transactionsLast30 = Deposits::whereStatus('approved')->whereDate('date', '=', $date)->count();

			// Format Date on Chart
			$formatDate = Helper::formatDateChart($date);
			$monthsData[] =  "'$formatDate'";

			// Revenue last 30 days
			$revenueSum[] = $deposits;

			// Transactions last 30 days
			$lastTransactions[] = $transactionsLast30;
		}

		// Today
		$stat_revenue_today = Deposits::whereStatus('approved')->where('date', '>=', Carbon::today())
			->sum('amount');

		// Yesterday
		$stat_revenue_yesterday = Deposits::whereStatus('approved')->where('date', '>=', Carbon::yesterday())
			->where('date', '<', Carbon::today())
			->sum('amount');

		// Week
		$stat_revenue_week = Deposits::whereStatus('approved')->whereBetween('date', [
			Carbon::parse('now')->startOfWeek(),
			Carbon::parse('now')->endOfWeek(),
		])->sum('amount');

		// Last Week
		$stat_revenue_last_week = Deposits::whereStatus('approved')->whereBetween('date', [
			Carbon::now()->startOfWeek()->subWeek(),
			Carbon::now()->subWeek()->endOfWeek(),
		])->sum('amount');

		// Month
		$stat_revenue_month = Deposits::whereStatus('approved')->whereBetween('date', [
			Carbon::parse('now')->startOfMonth(),
			Carbon::parse('now')->endOfMonth(),
		])->sum('amount');

		// Last Month
		$stat_revenue_last_month = Deposits::whereStatus('approved')->whereBetween('date', [
			Carbon::now()->startOfMonth()->subMonth(),
			Carbon::now()->subMonth()->endOfMonth(),
		])->sum('amount');

		$label = implode(',', array_reverse($monthsData));
		$data = implode(',', array_reverse($revenueSum));

		$dataLastTransactions = implode(',', array_reverse($lastTransactions));

		$totalUsers  = User::count();
		$totalTransactions = Deposits::whereStatus('approved')->count();


		return view('admin.dashboard', [
			'earningNetAdmin' => $totalRevenue,
			'label' => $label,
			'data' => $data,
			'datalastSales' => $dataLastTransactions,
			'totalUsers' => $totalUsers,
			'totalSales' => $totalTransactions,
			'stat_revenue_today' => $stat_revenue_today,
			'stat_revenue_yesterday' => $stat_revenue_yesterday,
			'stat_revenue_week' => $stat_revenue_week,
			'stat_revenue_last_week' => $stat_revenue_last_week,
			'stat_revenue_month' => $stat_revenue_month,
			'stat_revenue_last_month' => $stat_revenue_last_month
		]);
	}

	// START
	public function categories()
	{
		$data = Categories::orderBy('name')->get();

		return view('admin.categories', compact('data'));
	}

	public function addCategories()
	{
		return view('admin.add-categories');
	}

	public function storeCategories(Request $request)
	{
		$temp            = 'public/temp/'; // Temp
		$path            = 'public/img-category/'; // Path General

		Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
			return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		$rules = [
			'name'        => 'required',
			'thumbnail'   => 'image|dimensions:min_width=457,min_height=359',
			'date'        => 'nullable|date',
			'time'        => 'nullable|date_format:H:i',
		];

		$this->validate($request, $rules);

		if ($request->hasFile('thumbnail')) {

			$extension        = $request->file('thumbnail')->extension();
			$thumbnail        = str_slug($request->name) . '-' . str_random(32) . '.' . $extension;

			if ($request->file('thumbnail')->move($temp, $thumbnail)) {

				$image = Image::read($temp . $thumbnail);

				if ($image->width() == 457 && $image->height() == 359) {

					$image->encodeByExtension($extension)->save($path . $thumbnail);
				} else {
					$image->cover(width: 457, height: 359)
						->encodeByExtension($extension)
						->save($path . $thumbnail);
				}
				\File::delete($temp . $thumbnail);
			}
		} // HasFile

		else {
			$thumbnail = '';
		}

		$sql              = new Categories();
		$sql->name        = trim($request->name);
		$sql->thumbnail   = $thumbnail;
		$sql->mode        = $request->mode ?? 'off';
		$sql->date        = $request->date;
		$sql->time        = $request->time;
		$sql->save();

		return redirect('panel/admin/categories')->withSuccessMessage(__('admin.success_add_category'));
	}

	public function editCategories($id)
	{

		$categories = Categories::find($id);

		return view('admin.edit-categories')->with('categories', $categories);
	}

	public function updateCategories(Request $request)
	{


		$categories = Categories::findOrFail($request->id);
		$temp       = 'public/temp/'; // Temp
		$path       = 'public/img-category/'; // Path General

		Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
			return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		$rules = [
			'name'      => 'required',
			'thumbnail' => 'image|dimensions:min_width=457,min_height=359',
			'date'      => 'nullable|date',
			'time'      => 'nullable|date_format:H:i',
		];

		$this->validate($request, $rules);

		if ($request->hasFile('thumbnail')) {

			$extension        = $request->file('thumbnail')->getClientOriginalExtension();
			$type_mime_shot   = $request->file('thumbnail')->getMimeType();
			$sizeFile         = $request->file('thumbnail')->getSize();
			$thumbnail        = str_slug($request->name) . '-' . str_random(32) . '.' . $extension;

			if ($request->file('thumbnail')->move($temp, $thumbnail)) {

				$image = Image::read($temp . $thumbnail);

				if ($image->width() == 457 && $image->height() == 359) {
					$image->encodeByExtension($extension)->save($path . $thumbnail);

				} else {
					$image->cover(width: 457, height: 359)
						->encodeByExtension($extension)
						->save($path . $thumbnail);
				}

				\File::delete($temp . $thumbnail);

				// Delete Old Image
				\File::delete($path . $categories->thumbnail);
			} // End File
		} // HasFile
		else {
			$thumbnail = $categories->thumbnail;
		}

		// UPDATE CATEGORY
		$categories->name       = $request->name;
		$categories->thumbnail  = $thumbnail;
		$categories->mode       = $request->mode ?? 'off';
		$categories->date       = $request->date;
		$categories->time       = $request->time;
		$categories->save();

		return redirect('panel/admin/categories')->withSuccessMessage(__('misc.success_update'));
	}

	public function deleteCategories($id)
	{
		$categories = Categories::find($id);
		$thumbnail  = 'public/img-category/' . $categories->thumbnail; // Path General

		if (!isset($categories) || $categories->id == 1) {
			return redirect('panel/admin/categories');
		} else {
			// Delete Thumbnail
			if (\File::exists($thumbnail)) {
				\File::delete($thumbnail);
			} //<--- IF FILE EXISTS

		// Images functionality removed - this is now a universal starter kit
		// No need to update images table as it doesn't exist

			// Delete Category
			$categories->delete();

			return redirect('panel/admin/categories')->withSuccessMessage(__('admin.success_delete_category'));
		}
	}

	public function settings()
	{

		return view('admin.settings');
	}

	public function saveSettings(Request $request)
	{

		if ($request->captcha && !config('captcha.sitekey') && !config('captcha.secret')) {
			return back()->withErrors(['error' => __('misc.error_active_captcha')]);
		}


		$rules = array(
			'title'        => 'required',
			'link_terms'   => 'required|url',
			'link_privacy' => 'required|url',
			'link_license' => 'url',
			'link_blog'    => 'url'
		);

		$this->validate($request, $rules);

		$sql                      = AdminSettings::first();
		$sql->title               = $request->title;
		$sql->link_terms          = $request->link_terms;
		$sql->link_privacy        = $request->link_privacy;
		$sql->link_license        = $request->link_license;
		$sql->link_blog           = $request->link_blog;
		$sql->signup_bonus_credits = $request->signup_bonus_credits;
		$sql->captcha             = $request->captcha ?? 'off';
		$sql->registration_active = $request->registration_active ?? '0';
		$sql->email_verification  = $request->email_verification ?? '0';
		$sql->theme                = $request->theme;
		$sql->banner_cookies       = $request->banner_cookies ?? false;

		// SEO Settings - Commented out
		// $sql->seo_title            = $request->seo_title;
		// $sql->seo_description      = $request->seo_description;
		// $sql->seo_keywords         = $request->seo_keywords;
		// $sql->og_title             = $request->og_title;
		// $sql->og_description       = $request->og_description;
		// $sql->canonical_url        = $request->canonical_url;

		// Handle OG Image upload - Commented out (SEO section)
		// if ($request->hasFile('og_image')) {
		// 	$temp = 'public/temp/';
		// 	$path = 'public/img/';

		// 	$extension = $request->file('og_image')->getClientOriginalExtension();
		// 	$file = 'og-image-' . time() . '.' . $extension;

		// 	if ($request->file('og_image')->move($temp, $file)) {
		// 		\File::copy($temp . $file, $path . $file);
		// 		\File::delete($temp . $file);

		// 		// Delete old OG image if exists
		// 		if ($sql->og_image && \File::exists($path . $sql->og_image)) {
		// 			\File::delete($path . $sql->og_image);
		// 		}

		// 		$sql->og_image = $file;
		// 	}
		// }

		$sql->save();

		// Default locale
		Helper::envUpdate('DEFAULT_LOCALE', $request->default_language);

		// App Name
		Helper::envUpdate('APP_NAME', ' "' . $request->title . '" ', true);

		if ($this->settings->who_can_upload == 'all' && $request->who_can_upload == 'admin') {
			User::where('role', '<>', 1)->update([
				'authorized_to_upload' => 'no'
			]);
		} elseif ($this->settings->who_can_upload == 'admin' && $request->who_can_upload == 'all') {
			User::where('role', '<>', 1)->update([
				'authorized_to_upload' => 'yes'
			]);
		}

		return redirect('panel/admin/settings')->withSuccessMessage(__('admin.success_update'));
	}

	public function settingsLimits()
	{
		return view('admin.limits');
	}

	public function saveSettingsLimits(Request $request)
	{


		$sql                      = AdminSettings::first();
		$sql->result_request      = $request->result_request;
		$sql->limit_upload_user   = $request->limit_upload_user;
		$sql->daily_limit_downloads = $request->daily_limit_downloads;
		$sql->title_length        = $request->title_length;
		$sql->message_length      = $request->message_length;
		$sql->comment_length      = $request->comment_length;
		$sql->file_size_allowed   = $request->file_size_allowed;
		$sql->auto_approve_images = $request->auto_approve_images;
		$sql->downloads           = $request->downloads;
		$sql->tags_limit          = $request->tags_limit;
		$sql->description_length  = $request->description_length;
		$sql->min_width_height_image = $request->min_width_height_image;
		$sql->file_size_allowed_vector = $request->file_size_allowed_vector;

		$sql->save();

		\Session::flash('success_message', trans('admin.success_update'));

		return redirect('panel/admin/settings/limits');
	}

	public function members_reported()
	{

		$data = UsersReported::orderBy('id', 'DESC')->get();

		return view('admin.members_reported', compact('data'));
	}

	public function delete_members_reported(Request $request)
	{

		$report = UsersReported::find($request->id);

		if (isset($report)) {
			$report->delete();
		}

		return redirect('panel/admin/members-reported');
	}

	/* COMMENTED OUT - Stock photo related functionality
	public function images_reported()
	{

		$data = ImagesReported::orderBy('id', 'DESC')->get();

		//dd($data);

		return view('admin.images_reported', compact('data'));
	}

	public function delete_images_reported(Request $request)
	{

		$report = ImagesReported::find($request->id);

		if (isset($report)) {
			$report->delete();
		}

		return redirect('panel/admin/images-reported');
	}
	END COMMENTED OUT */

	/* COMMENTED OUT - Stock photo related functionality
	public function images()
	{
		$query = request()->get('q');
		$sort = request()->get('sort');
		$pagination = 15;

		$data = Images::orderBy('id', 'desc')->paginate($pagination);

		// Search
		if (isset($query)) {
			$data = Images::where('title', 'LIKE', '%' . $query . '%')
				->orWhere('tags', 'LIKE', '%' . $query . '%')
				->orderBy('id', 'desc')->paginate($pagination);
		}

		// Sort
		if (isset($sort) && $sort == 'title') {
			$data = Images::orderBy('title', 'asc')->paginate($pagination);
		}

		if (isset($sort) && $sort == 'pending') {
			$data = Images::where('status', 'pending')->paginate($pagination);
		}

		if (isset($sort) && $sort == 'downloads') {
			$data = Images::join('downloads', 'images.id', '=', 'downloads.images_id')
				->groupBy('downloads.images_id')
				->orderBy(\DB::raw('COUNT(downloads.images_id)'), 'desc')
				->select('images.*')
				->paginate($pagination);
		}

		if (isset($sort) && $sort == 'likes') {
			$data = Images::join('likes', function ($join) {
				$join->on('likes.images_id', '=', 'images.id')->where('likes.status', '=', '1');
			})
				->groupBy('likes.images_id')
				->orderBy(\DB::raw('COUNT(likes.images_id)'), 'desc')
				->select('images.*')
				->paginate($pagination);
		}

		// return view('admin.images', ['data' => $data, 'query' => $query, 'sort' => $sort]);
	}
	END STOCK PHOTO METHODS */

	// Image management methods removed for universal starter kit

	public function profiles_social()
	{
		return view('admin.profiles-social');
	}

	public function update_profiles_social(Request $request)
	{
		$sql = AdminSettings::find(1);

		$rules = array(
			'twitter'    => 'url',
			'facebook'   => 'url',
			'linkedin'   => 'url',
			'instagram'  => 'url',
			'youtube'  => 'url',
			'pinterest'  => 'url',
		);

		$this->validate($request, $rules);

		$sql->twitter       = $request->twitter;
		$sql->facebook      = $request->facebook;
		$sql->linkedin      = $request->linkedin;
		$sql->instagram     = $request->instagram;
		$sql->youtube     = $request->youtube;
		$sql->pinterest     = $request->pinterest;

		$sql->save();

		\Session::flash('success_message', trans('admin.success_update'));

		return redirect('panel/admin/profiles-social');
	}

	public function google()
	{
		return view('admin.google');
	}

	public function update_google(Request $request)
	{
		$sql = AdminSettings::first();

		$sql->google_adsense_index = $request->google_adsense_index;
		$sql->google_adsense   = $request->google_adsense;
		$sql->google_analytics = $request->google_analytics;
		$sql->save();

		foreach ($request->except(['_token']) as $key => $value) {
			Helper::envUpdate($key, $value);
		}

		return redirect('panel/admin/google')->withSuccessMessage(__('admin.success_update'));
	}

	public function theme()
	{
		return view('admin.theme');
	}

	public function themeStore(Request $request)
	{
		$temp  = 'public/temp/'; // Temp
		$path  = 'public/img/'; // Path
		$pathAvatar = config('path.avatar');
		$pathCover = config('path.cover');
		$pathCategory = 'public/img-category/'; // Path Category

		$rules = [
			'logo'   => 'image',
			'logo_light' => 'image',
			'favicon'   => 'image',
			'image_header'   => 'image',
			'img_section'   => 'image',
		];

		$this->validate($request, $rules);

		//========== LOGO
		if ($request->hasFile('logo')) {

			$extension = $request->file('logo')->getClientOriginalExtension();
			$file      = 'logo-' . time() . '.' . $extension;

			if ($request->file('logo')->move($temp, $file)) {
				\File::copy($temp . $file, $path . $file);
				\File::delete($temp . $file);
				\File::delete($path . $this->settings->logo);
			} // End File

			$this->settings->logo = $file;
			$this->settings->save();
		} // HasFile

		//========== LOGO
		if ($request->hasFile('logo_light')) {

			$extension = $request->file('logo_light')->getClientOriginalExtension();
			$file      = 'logo_light-' . time() . '.' . $extension;

			if ($request->file('logo_light')->move($temp, $file)) {
				\File::copy($temp . $file, $path . $file);
				\File::delete($temp . $file);
				\File::delete($path . $this->settings->logo_light);
			} // End File

			$this->settings->logo_light = $file;
			$this->settings->save();
		} // HasFile

		//======== FAVICON
		if ($request->hasFile('favicon')) {

			$extension  = $request->file('favicon')->getClientOriginalExtension();
			$file       = 'favicon-' . time() . '.' . $extension;

			if ($request->file('favicon')->move($temp, $file)) {
				\File::copy($temp . $file, $path . $file);
				\File::delete($temp . $file);
				\File::delete($path . $this->settings->favicon);
			} // End File

			$this->settings->favicon = $file;
			$this->settings->save();
		} // HasFile

		//======== image_header
		if ($request->hasFile('image_header')) {

			$extension  = $request->file('image_header')->getClientOriginalExtension();
			$file       = 'header_index-' . time() . '.' . $extension;

			if ($request->file('image_header')->move($temp, $file)) {
				\File::copy($temp . $file, $path . $file);
				\File::delete($temp . $file);
				\File::delete($path . $this->settings->image_header);
			} // End File

			$this->settings->image_header = $file;
			$this->settings->save();
		} // HasFile

		//======== img_section
		if ($request->hasFile('img_section')) {

			$extension  = $request->file('img_section')->getClientOriginalExtension();
			$file       = 'img_section-' . time() . '.' . $extension;

			if ($request->file('img_section')->move($temp, $file)) {
				\File::copy($temp . $file, $path . $file);
				\File::delete($temp . $file);
				\File::delete($path . $this->settings->img_section);
			} // End File

			$this->settings->img_section = $file;
			$this->settings->save();
		} // HasFile

		//======== Watermark


		// Update Color Default, and Button style
		$this->settings->whereId(1)
			->update([
				'color_default' => $request->color_default
			]);

		//======= CLEAN CACHE
		\Artisan::call('cache:clear');

		return redirect('panel/admin/theme')
			->withSuccessMessage(__('misc.success_update'));
	}

	public function payments()
	{
		return view('admin.payments-settings');
	}

	public function savePayments(Request $request)
	{
		$sql = AdminSettings::first();

		$rules = [
			'currency_code' => 'required|alpha|max:3',
			'currency_symbol' => 'required|max:10',
			'currency_position' => 'required|in:left,right',
			'decimal_format' => 'required|in:dot,comma'
		];

		$this->validate($request, $rules);

		$sql->currency_code = strtoupper($request->currency_code);
		$sql->currency_symbol = $request->currency_symbol;
		$sql->currency_position = $request->currency_position;
		$sql->decimal_format = $request->decimal_format;

		$sql->save();

		\Session::flash('success_message', trans('admin.success_update'));

		return redirect('panel/admin/payments');
	}

	/* COMMENTED OUT - Stock photo related functionality
	public function purchases()
	{
		$data = Purchases::with(['images', 'invoice'])->whereApproved('1')->orderBy('id', 'desc')->paginate(30);
		return view('admin.purchases', compact('data'));
	}
	END COMMENTED OUT */

	public function depositsView($id)
	{
		$data = Deposits::findOrFail($id);
		return view('admin.deposits-view', compact('data'));
	}

	public function approveDeposits(Request $request)
	{
		$query = Deposits::with(['invoicePending'])->findOrFail($request->id);

		$data = [
			'type' => 'approve',
			'amount' => Helper::amountFormat($query->amount),
			'name' => $query->user()->name
		];

		// Send Mail to User
		try {
			$query->user()->notify(new DepositVerification($data));
		} catch (\Exception $e) {
			return back()->withErrors([
				'errors' => $e->getMessage(),
			]);
		}

		$query->status = 'active';
		$query->save();

		// Add Funds to User
		$query->user()->increment('funds', $query->amount);

		// Update Invoice
		$query->invoicePending->update([
			'status' => 'paid'
		]);

		return redirect('panel/admin/deposits');
	}

	public function deleteDeposits(Request $request)
	{
		$path = config('path.admin');
		$query = Deposits::with(['invoicePending'])->findOrFail($request->id);

		if (isset($query->user()->name)) {
			$data = [
				'type' => 'not_approve',
				'amount' => Helper::amountFormat($query->amount),
				'name' => $query->user()->name
			];

			// Send Mail to User
			try {
				$query->user()->notify(new DepositVerification($data));
			} catch (\Exception $e) {
				return back()->withErrors([
					'errors' => $e->getMessage(),
				]);
			}
		}

		// Delete Image
		Storage::delete($path . $query->screenshot_transfer);

		// Delete Invoice
		$query->invoicePending->delete();

		$query->delete();

		return redirect('panel/admin/deposits');
	}


	public function paymentsGateways($id)
	{
		$data = PaymentGateways::findOrFail($id);
		$name = ucfirst($data->name);

		return view('admin.' . str_slug($name) . '-settings')->withData($data);
	}

	public function savePaymentsGateways($id, Request $request)
	{

		$data = PaymentGateways::findOrFail($id);

		$input = $_POST;

		// Sandbox off
		if (!$request->sandbox) {
			$input['sandbox'] = 'false';
		}

		// Enabled off
		if (!$request->enabled) {
			$input['enabled'] = '0';
		}

		$this->validate($request, [
			'email'    => 'email',
		]);

		$data->fill($input)->save();

		// Set Stripe Keys
		if ($data->name == 'Stripe') {
			Helper::envUpdate('STRIPE_KEY', $input['key']);
			Helper::envUpdate('STRIPE_SECRET', $input['key_secret']);
			Helper::envUpdate('STRIPE_WEBHOOK_SECRET', $input['webhook_secret']);
		}

		// Set PayPal Keys on .env file
		if ($data->name == 'PayPal') {
			if (!$request->sandbox) {
				Helper::envUpdate('PAYPAL_MODE', 'live');
				Helper::envUpdate('PAYPAL_LIVE_CLIENT_ID', $input['key']);
				Helper::envUpdate('PAYPAL_LIVE_CLIENT_SECRET', $input['key_secret']);
			} else {
				Helper::envUpdate('PAYPAL_MODE', 'sandbox');
				Helper::envUpdate('PAYPAL_SANDBOX_CLIENT_ID', $input['key']);
				Helper::envUpdate('PAYPAL_SANDBOX_CLIENT_SECRET', $input['key_secret']);
			}

			Helper::envUpdate('PAYPAL_WEBHOOK_ID', $input['webhook_secret']);
		} // PayPal

		// Set Paystack Keys
		if ($data->name == 'Paystack') {
			Helper::envUpdate('PAYSTACK_PUBLIC_KEY', $input['key']);
			Helper::envUpdate('PAYSTACK_SECRET_KEY', $input['key_secret']);
			Helper::envUpdate('MERCHANT_EMAIL', $input['email']);
		}

		// Set Flutterwave Keys
		if ($data->name == 'Flutterwave') {
			Helper::envUpdate('FLW_PUBLIC_KEY', $input['key']);
			Helper::envUpdate('FLW_SECRET_KEY', $input['key_secret']);
		}

		return back()->withSuccessMessage(__('admin.success_update'));
	}

	public function maintenance(Request $request)
	{
		$strRandom = str_random(50);

		if ($request->maintenance_mode) {
			\Artisan::call('down', [
				'--secret' => $strRandom
			]);
		} elseif (!$request->maintenance_mode) {
			\Artisan::call('up');
		}

		$this->settings->maintenance_mode = $request->maintenance_mode;
		$this->settings->save();

		if ($request->maintenance_mode) {
			return redirect($strRandom)
				->withSuccessMessage(trans('misc.maintenance_mode_on'));
		} else {
			return redirect('panel/admin/maintenance')
				->withSuccessMessage(trans('misc.maintenance_mode_off'));
		}
	}

	// Show billing page with payment methods
	public function billing()
	{
		$paymentMethods = PaymentMethod::ordered()->get();
		return view('admin.billing', compact('paymentMethods'));
	}

	// Store new payment method
	public function storePaymentMethod(Request $request)
	{
		$request->validate([
			'bank_or_account_name' => 'required|string|max:255',
			'account_title' => 'required|string|max:255',
			'account_no' => 'required|string|max:100',
			'bank_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
			'is_active' => 'required|in:0,1',
			'sort_order' => 'integer|min:0'
		]);

		$paymentMethod = new PaymentMethod();
		$paymentMethod->bank_or_account_name = $request->bank_or_account_name;
		$paymentMethod->account_title = $request->account_title;
		$paymentMethod->account_no = $request->account_no;
		$paymentMethod->is_active = (bool) $request->is_active;
		$paymentMethod->sort_order = $request->sort_order ?? 0;

		// Handle image upload
		if ($request->hasFile('bank_image')) {
			$paymentMethod->bank_image = $this->handleImageUpload($request->file('bank_image'));
		}

		$paymentMethod->save();

		return back()->withSuccessMessage('Payment method added successfully.');
	}

	// Get payment method for editing
	public function getPaymentMethod($id)
	{
		$paymentMethod = PaymentMethod::findOrFail($id);
		return response()->json($paymentMethod);
	}

	// Update payment method
	public function updatePaymentMethod(Request $request)
	{
		$request->validate([
			'method_id' => 'required|exists:payment_methods,id',
			'bank_or_account_name' => 'required|string|max:255',
			'account_title' => 'required|string|max:255',
			'account_no' => 'required|string|max:100',
			'bank_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
			'is_active' => 'required|in:0,1',
			'sort_order' => 'integer|min:0'
		]);

		$paymentMethod = PaymentMethod::findOrFail($request->method_id);
		$paymentMethod->bank_or_account_name = $request->bank_or_account_name;
		$paymentMethod->account_title = $request->account_title;
		$paymentMethod->account_no = $request->account_no;
		$paymentMethod->is_active = (bool) $request->is_active;
		$paymentMethod->sort_order = $request->sort_order ?? 0;

		// Handle image upload
		if ($request->hasFile('bank_image')) {
			// Delete old image if exists
			if ($paymentMethod->bank_image && \File::exists('public/img/' . $paymentMethod->bank_image)) {
				\File::delete('public/img/' . $paymentMethod->bank_image);
			}
			$paymentMethod->bank_image = $this->handleImageUpload($request->file('bank_image'));
		}

		$paymentMethod->save();

		return back()->withSuccessMessage('Payment method updated successfully.');
	}

	// Delete payment method
	public function deletePaymentMethod($id)
	{
		$paymentMethod = PaymentMethod::findOrFail($id);

		// Delete associated image
		if ($paymentMethod->bank_image && \File::exists('public/img/' . $paymentMethod->bank_image)) {
			\File::delete('public/img/' . $paymentMethod->bank_image);
		}

		$paymentMethod->delete();

		return back()->withSuccessMessage('Payment method deleted successfully.');
	}

	// Helper method for image upload
	private function handleImageUpload($file)
	{
		try {
			$temp = 'public/temp/';
			$path = 'public/img/';

			// Ensure directories exist
			if (!\File::exists($temp)) {
				\File::makeDirectory($temp, 0755, true);
			}
			if (!\File::exists($path)) {
				\File::makeDirectory($path, 0755, true);
			}

			$extension = $file->getClientOriginalExtension();
			$fileName = 'payment-method-' . time() . '-' . uniqid() . '.' . $extension;

			// Move file to temp directory first
			if ($file->move($temp, $fileName)) {
				// Copy to final location
				if (\File::copy($temp . $fileName, $path . $fileName)) {
					// Delete temp file
					\File::delete($temp . $fileName);
					return $fileName;
				} else {
					// Clean up temp file if copy failed
					\File::delete($temp . $fileName);
					throw new \Exception('Failed to save image');
				}
			} else {
				throw new \Exception('Failed to upload image');
			}
		} catch (\Exception $e) {
			\Log::error('Payment method image upload error: ' . $e->getMessage());
			throw $e;
		}
	}

	// Method to delete bank image
	public function deleteBankImage()
	{
		try {
			$path = 'public/img/';

			// Delete the image file if it exists
			if ($this->settings->bank_image && \File::exists($path . $this->settings->bank_image)) {
				\File::delete($path . $this->settings->bank_image);
			}

			// Remove from database
			$this->settings->bank_image = null;
			$this->settings->save();

			return back()->withSuccessMessage('Bank image deleted successfully.');
		} catch (\Exception $e) {
			\Log::error('Bank image deletion error: ' . $e->getMessage());
			return back()->withErrors(['error' => 'Failed to delete bank image. Please try again.']);
		}
	}

	public function emailSettings(Request $request)
	{
		$request->validate([
			'MAIL_FROM_ADDRESS' => 'required'
		]);

		$request->MAIL_ENCRYPTION = strtolower($request->MAIL_ENCRYPTION);

		$this->settings->email_admin = $request->email_admin;
		$this->settings->email_no_reply = $request->MAIL_FROM_ADDRESS;
		$this->settings->save();

		foreach ($request->except(['_token']) as $key => $value) {
			Helper::envUpdate($key, $value);
		}

		return back()->withSuccessMessage(trans('admin.success_update'));
	} // End Method

	public function storage(Request $request)
	{
		$messages = [
			'APP_URL.required' => trans('validation.required', ['attribute' => 'App URL']),
			'APP_URL.url' => trans('validation.url', ['attribute' => 'App URL'])
		];

		$request->validate([
			'APP_URL' => 'required|url',
			'AWS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,==,s3',
			'AWS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,==,s3',
			'AWS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,==,s3',
			'AWS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,==,s3',

			'DOS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,==,dospace',
			'DOS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,==,dospace',
			'DOS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,==,dospace',
			'DOS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,==,dospace',

			'WAS_ACCESS_KEY_ID' => 'required_if:FILESYSTEM_DRIVER,==,wasabi',
			'WAS_SECRET_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,==,wasabi',
			'WAS_DEFAULT_REGION' => 'required_if:FILESYSTEM_DRIVER,==,wasabi',
			'WAS_BUCKET' => 'required_if:FILESYSTEM_DRIVER,==,wasabi',

			'VULTR_ACCESS_KEY' => 'required_if:FILESYSTEM_DRIVER,==,vultr',
			'VULTR_SECRET_KEY' => 'required_if:FILESYSTEM_DRIVER,==,vultr',
			'VULTR_REGION' => 'required_if:FILESYSTEM_DRIVER,==,vultr',
			'VULTR_BUCKET' => 'required_if:FILESYSTEM_DRIVER,==,vultr',
		], $messages);

		foreach ($request->except(['_token']) as $key => $value) {

			if ($value == $request->APP_URL) {
				$value = trim($value, '/');
			}

			Helper::envUpdate($key, $value);
		}

		return back()->withSuccessMessage(trans('admin.success_update'));
	} // End Method

	public function updateSocialLogin(Request $request)
	{
		$this->settings->facebook_login = $request->facebook_login ?? 'off';
		$this->settings->google_login   = $request->google_login ?? 'off';
		$this->settings->twitter_login  = $request->twitter_login ?? 'off';
		$this->settings->save();

		foreach ($request->except(['_token']) as $key => $value) {
			Helper::envUpdate($key, $value);
		}

		\Session::flash('success_message', trans('admin.success_update'));
		return back();
	}

	public function pwa(Request $request)
	{
		$allImgs = $request->file('files');

		if ($allImgs) {
			foreach ($allImgs as $key => $file) {

				$filename = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
				$file->move(public_path('images/icons'), $filename);

				\File::delete(env($key));

				$envIcon = 'public/images/icons/' . $filename;
				Helper::envUpdate($key, $envIcon);
			}
		}

		// Updaye Short Name
		Helper::envUpdate('PWA_SHORT_NAME', ' "' . $request->PWA_SHORT_NAME . '" ', true);

		$sql = $this->settings;
		$sql->status_pwa = $request->status_pwa;
		$sql->save();

		\Artisan::call('cache:clear');
		\Artisan::call('view:clear');

		return back()->withSuccessMessage(trans('admin.success_update'));
	}

	public function subscriptions()
	{
		$subscriptions = Subscriptions::orderBy('id', 'DESC')->paginate(50);
		return view('admin.subscriptions', ['subscriptions' => $subscriptions]);
	}

	/* COMMENTED OUT - Stock photo related functionality
	public function collections()
	{
		$data = Collections::with('collectionImages')
			->with('creator')
			->orderBy('id', 'DESC')->paginate(30);

		return view('admin.collections', compact('data'));
	}

	public function deleteCollection(Request $request)
	{
		$collection = Collections::findOrFail($request->id);

		// Delete images on collection
		CollectionsImages::whereCollectionsId($collection->id)->delete();

		$collection->delete();

		return redirect('panel/admin/collections');
	}
	END COMMENTED OUT */

	public function clearCache()
	{
		// Clear Cache, Config and Views
		\Artisan::call('cache:clear');
		\Artisan::call('config:clear');
		\Artisan::call('view:clear');

		$pathLogFile = storage_path("logs" . DIRECTORY_SEPARATOR . "laravel.log");

		try {
			collect(Storage::disk('default')->listContents('.cache', true))
				->each(function ($file) {
					Storage::disk('default')->deleteDirectory($file['path']);
					Storage::disk('default')->delete($file['path']);
				});

			// Delete Log file
			if (auth()->user()->isSuperAdmin()) {
				if (file_exists($pathLogFile)) {
					unlink($pathLogFile);
				}
			}
		} catch (\Exception $e) {
		}

		return redirect('panel/admin/maintenance')
			->withSuccessMessage(trans('admin.successfully_cleaned'));
	} // End method

	public function customCssJs(Request $request)
	{
		$sql = $this->settings;
		$sql->custom_css = $request->custom_css;
		$sql->custom_js = $request->custom_js;
		$sql->save();

		return back()->withSuccessMessage(trans('admin.success_update'));
	} // End method

	public function storeAnnouncements(Request $request)
	{
		$this->settings->announcement = $request->announcement_content;
		$this->settings->type_announcement = $request->type_announcement;
		$this->settings->announcement_show = $request->announcement_show;
		$this->settings->announcement_cookie = str_random(25);
		$this->settings->save();

		return back()->withSuccessMessage(trans('admin.success_update'));
	} // End method

	public function subcategories()
	{
		$subcategories = Subcategories::with(['category'])->orderBy('name')->paginate(20);
		$totalSubcategoriesCategories = $subcategories->count();

		return view('admin.subcategories')->with([
			'subcategories' => $subcategories,
			'totalSubcategoriesCategories' => $totalSubcategoriesCategories,
		]);
	}

	public function addSubcategories()
	{
		return view('admin.add-subcategories');
	}

	public function storeSubcategories(Request $request)
	{
		Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
			return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		$rules = [
			'name' => 'nullable',
			'category' => 'required',
			'start_date' => 'nullable|date',
			'start_time' => 'nullable|date_format:H:i',
			'close_date' => 'nullable|date',
			'close_time' => 'nullable|date_format:H:i',
		];

		$this->validate($request, $rules);

		$sql              = new Subcategories();
		$sql->name        = $request->name ? trim($request->name) : null;
		$sql->category_id = $request->category;
		$sql->mode        = $request->mode ?? 'off';
		$sql->start_date  = $request->start_date;
		$sql->start_time  = $request->start_time;
		$sql->close_date  = $request->close_date;
		$sql->close_time  = $request->close_time;
		$sql->save();

		return redirect('panel/admin/subcategories')
			->withSuccessMessage(__('misc.successfully_added'));
	}

	public function editSubcategories($id)
	{
		$subcategory = Subcategories::find($id);

		return view('admin.edit-subcategories')->with('subcategory', $subcategory);
	}

	public function updateSubcategories(Request $request)
	{
		$subcategory = Subcategories::findOrFail($request->id);

		Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
			return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		$rules = [
			'name'      => 'nullable',
			'category' => 'required',
			'start_date' => 'nullable|date',
			'start_time' => 'nullable|date_format:H:i',
			'close_date' => 'nullable|date',
			'close_time' => 'nullable|date_format:H:i',
		];

		$this->validate($request, $rules);

		// UPDATE SUBCATEGORY
		$subcategory->name       = $request->name ? trim($request->name) : null;
		$subcategory->category_id  = $request->category;
		$subcategory->mode       = $request->mode ?? 'off';
		$subcategory->start_date = $request->start_date;
		$subcategory->start_time = $request->start_time;
		$subcategory->close_date = $request->close_date;
		$subcategory->close_time = $request->close_time;
		$subcategory->save();

		return redirect('panel/admin/subcategories')
			->withSuccessMessage(__('misc.success_update'));
	}

	public function deleteSubcategories($id)
	{
		Subcategories::find($id)->delete();

		return redirect('panel/admin/subcategories')
			->withSuccessMessage(__('misc.successfully_removed'));
	}

	public function savePushNotifications(Request $request)
	{
		$this->settings->push_notification_status  = $request->push_notification_status;
		$this->settings->onesignal_appid           = $request->onesignal_appid;
		$this->settings->onesignal_restapi         = $request->onesignal_restapi;
		$this->settings->save();

		return back()->withSuccessMessage(__('admin.success_update'));
	}

	// Deposit Management
	public function deposits()
	{
		$allDeposits = Deposits::with(['user', 'paymentMethod'])->latest()->paginate(20);
		$pendingDeposits = Deposits::with(['user', 'paymentMethod'])->pending()->latest()->paginate(20);
		$approvedDeposits = Deposits::with(['user', 'paymentMethod'])->approved()->latest()->paginate(20);
		$rejectedDeposits = Deposits::with(['user', 'paymentMethod'])->rejected()->latest()->paginate(20);

		return view('admin.deposits', compact('allDeposits', 'pendingDeposits', 'approvedDeposits', 'rejectedDeposits'));
	}

	public function approveDeposit(Request $request)
	{
		$request->validate([
			'deposit_id' => 'required|exists:deposits,id',
			'admin_notes' => 'nullable|string|max:1000'
		]);

		$deposit = Deposits::findOrFail($request->deposit_id);

		if ($deposit->status !== 'pending') {
			return back()->withErrorMessage('This deposit has already been processed.');
		}

		$deposit->status = 'approved';
		$deposit->admin_notes = $request->admin_notes;
		$deposit->save();

		// Add amount to user's balance
		$user = $deposit->user;
		$user->balance += $deposit->amount;
		$user->save();

		// Send notification to user
		$deposit->user->notify(new \App\Notifications\DepositVerification($deposit, 'approved'));

		return back()->withSuccessMessage('Deposit approved successfully. Amount has been added to user balance.');
	}

	public function rejectDeposit(Request $request)
	{
		$request->validate([
			'deposit_id' => 'required|exists:deposits,id',
			'admin_notes' => 'required|string|max:1000'
		]);

		$deposit = Deposits::findOrFail($request->deposit_id);

		if ($deposit->status !== 'pending') {
			return back()->withErrorMessage('This deposit has already been processed.');
		}

		$deposit->status = 'rejected';
		$deposit->admin_notes = $request->admin_notes;
		$deposit->save();

		// Send notification to user
		$deposit->user->notify(new \App\Notifications\DepositVerification($deposit, 'rejected'));

		return back()->withSuccessMessage('Deposit rejected successfully.');
	}
}
