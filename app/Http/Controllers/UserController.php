<?php

namespace App\Http\Controllers;

use DB;
use App\Models\User;
use App\Models\Query;
use App\Models\Invoices;
use App\Models\TaxRates;
use App\Models\Followers;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\Notifications;
use App\Models\UsersReported;
use App\Models\CollectionsImages;
use App\Models\ReferralTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;

class UserController extends Controller
{

	use Traits\UserTrait;

	public function __construct(AdminSettings $settings)
	{
		$this->settings = $settings::first();
	}

	protected function validator(array $data, $id = null)
	{

		Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
			return !preg_match('/[^x00-x7F\-]/i', $value);
		});

		// Validate if have one letter
		Validator::extend('letters', function ($attribute, $value, $parameters) {
			return preg_match('/[a-zA-Z0-9]/', $value);
		});

		return Validator::make($data, [
			'full_name' => 'required|min:3|max:25',
			'username'  => 'required|min:3|max:15|ascii_only|alpha_dash|letters|unique:pages,slug|unique:reserved,name|unique:users,username,' . $id,
			'email'     => 'required|email|unique:users,email,' . $id,
			'countries_id' => 'required',
			'paypal_account' => 'email',
			'website'   => 'url',
			'facebook'   => 'url',
			'twitter'   => 'url',
			'instagram'   => 'url',
			'description' => 'max:200',
		]);
	} //<--- End Method

	public function profile($slug, Request $request)
	{
		$user  = User::where('username', '=', $slug)
			->withCount(['followers', 'following'])
			->whereStatus('active')
			->firstOrFail();
		$title = $user->name ?: $user->username;

		// Images functionality removed - this is now a universal starter kit
				// Get user images - removed for universal starter kit
		$images = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
			'path' => request()->url(),
		]);

		// Pagination check removed since we don't have images anymore

		//<<<-- * Redirect the user real name * -->>>
		$uri = request()->path();
		$uriCanonical = $user->username;

		if ($uri != $uriCanonical) {
			return redirect($uriCanonical);
		}

		if (auth()->check()) {
			// Follow Active
			$followActive = Followers::whereFollower(auth()->id())
				->where('following', $user->id)
				->where('status', '1')
				->first();

			if ($followActive) {
				$textFollow   = __('users.following');
				$icoFollow    = '-person-check';
				$activeFollow = 'btnFollowActive';
			} else {
				$textFollow   = __('users.follow');
				$icoFollow    = '-person-plus';
				$activeFollow = '';
			}
		}

		if (request()->ajax()) {
			return view('includes.images', ['images' => $images])->render();
		}

		return view('users.profile', [
			'title' => $title,
			'user' => $user,
			'images' => $images,
			'textFollow' => $textFollow ?? null,
			'icoFollow' => $icoFollow ?? null,
			'activeFollow' => $activeFollow ?? null
		]);
	} //<--- End Method

	public function followers($slug, Request $request)
	{

		$user  = User::where('username', '=', $slug)
			->withCount(['followers', 'following'])
			->firstOrFail();
		$_title = $user->name ?: $user->username;
		$title  = $_title . ' - ' . __('users.followers');

		if ($user->status == 'suspended') {
			return view('errors.user_suspended');
		}

		$followers = User::where('users.status', 'active')
			->leftjoin('followers', 'users.id', '=', \DB::raw('followers.follower AND followers.status = "1"'))
			->where('users.status', '=', 'active')
			->where('followers.following', $user->id)
			->groupBy('users.id')
			->orderBy('followers.id', 'DESC')
			->select(
				'users.id',
				'users.username',
				'users.name',
				'users.avatar',
				'users.cover',
				'users.status'
			)
			->withCount(['followers'])
			->paginate(10);

		if ($request->input('page') > $followers->lastPage()) {
			abort('404');
		}

		if (request()->ajax()) {
			return view('includes.users', ['users' => $followers])->render();
		}

		//<<<-- * Redirect the user real name * -->>>
		$uri = request()->path();
		$uriCanonical = $user->username . '/followers';

		if ($uri != $uriCanonical) {
			return redirect($uriCanonical);
		}

		if (auth()->check()) {
			// Follow Active
			$followActive = Followers::whereFollower(auth()->id())
				->where('following', $user->id)
				->where('status', '1')
				->first();

			if ($followActive) {
				$textFollow   = __('users.following');
				$icoFollow    = '-person-check';
				$activeFollow = 'btnFollowActive';
			} else {
				$textFollow   = __('users.follow');
				$icoFollow    = '-person-plus';
				$activeFollow = '';
			}
		}

		return view('users.profile', [
			'title' => $title,
			'followers' => $followers,
			'user' => $user,
			'textFollow' => $textFollow ?? null,
			'icoFollow' => $icoFollow ?? null,
			'activeFollow' => $activeFollow ?? null,

		]);
	} //<--- End Method

	public function following($slug, Request $request)
	{

		$user  = User::where('username', '=', $slug)
			->withCount(['followers', 'following'])
			->firstOrFail();
		$_title = $user->name ?: $user->username;
		$title  = $_title . ' - ' . __('users.following');

		if ($user->status == 'suspended') {
			return view('errors.user_suspended');
		}

		$following = User::where('users.status', 'active')
			->leftjoin('followers', 'users.id', '=', \DB::raw('followers.following AND followers.status = "1"'))
			->where('users.status', '=', 'active')
			->where('followers.follower', $user->id)
			->groupBy('users.id')
			->orderBy('followers.id', 'DESC')
			->select(
				'users.id',
				'users.username',
				'users.name',
				'users.avatar',
				'users.cover',
				'users.status'
			)
			->withCount(['followers'])
			->paginate(10);

		if ($request->input('page') > $following->lastPage()) {
			abort('404');
		}

		if (request()->ajax()) {
			return view('includes.users', ['users' => $following])->render();
		}

		//<<<-- * Redirect the user real name * -->>>
		$uri = request()->path();
		$uriCanonical = $user->username . '/following';

		if ($uri != $uriCanonical) {
			return redirect($uriCanonical);
		}

		if (auth()->check()) {
			// Follow Active
			$followActive = Followers::whereFollower(auth()->id())
				->where('following', $user->id)
				->where('status', '1')
				->first();

			if ($followActive) {
				$textFollow   = __('users.following');
				$icoFollow    = '-person-check';
				$activeFollow = 'btnFollowActive';
			} else {
				$textFollow   = __('users.follow');
				$icoFollow    = '-person-plus';
				$activeFollow = '';
			}
		}

		return view('users.profile', [
			'title' => $title,
			'following' => $following,
			'user' => $user,
			'textFollow' => $textFollow ?? null,
			'icoFollow' => $icoFollow ?? null,
			'activeFollow' => $activeFollow ?? null,
		]);
	} //<--- End Method

	public function account()
	{
		return view('users.account');
	} //<--- End Method

	public function update_account(Request $request)
	{

		$input = $request->all();
		$id    = auth()->user()->id;

		$validator = $this->validator($input, $id);

		if ($validator->fails()) {
			return redirect()->back()
				->withErrors($validator)
				->withInput();
		}

		$user = User::find($id);
		$user->name        = $input['full_name'];
		$user->email        = trim($input['email']);
		$user->username = $input['username'];
		$user->countries_id    = $input['countries_id'];
		$user->author_exclusive = $input['author_exclusive'] ?? auth()->user()->author_exclusive;
		$user->paypal_account = trim($input['paypal_account']);
		$user->website     = trim(strtolower($input['website']));
		$user->facebook  = trim(strtolower($input['facebook']));
		$user->twitter       = trim(strtolower($input['twitter']));
		$user->instagram  = trim(strtolower($input['instagram']));
		$user->bio = $input['description'];
		$user->two_factor_auth = $input['two_factor_auth'] ?? 'no';
		$user->save();

		\Session::flash('notification', __('auth.success_update'));

		return redirect('account');
	} //<--- End Method

	public function password()
	{
		return view('users.password');
	} //<--- End Method

	public function update_password(Request $request)
	{

		$input = $request->all();
		$id = auth()->user()->id;

		$validator = Validator::make($input, [
			'old_password' => 'required|min:6',
			'password'     => 'required|min:8',
		]);

		if ($validator->fails()) {
			return redirect()->back()
				->withErrors($validator)
				->withInput();
		}

		if (!\Hash::check($input['old_password'], auth()->user()->password)) {
			return redirect('account/password')->with(array('incorrect_pass' => __('misc.password_incorrect')));
		}

		$user = User::find($id);
		$user->password  = \Hash::make($input["password"]);
		$user->save();

		\Session::flash('notification', __('auth.success_update_password'));

		return redirect('account/password');
	} //<--- End Method

	public function delete()
	{
		if (auth()->user()->id == 1) {
			return redirect('account');
		}
		return view('users.delete');
	} //<--- End Method

	public function delete_account()
	{

		$id = auth()->user()->id;
		$user = User::findOrFail($id);

		if ($user->id == 1) {
			return redirect('account');
			exit;
		}

		$this->deleteUser($id);

		return redirect('account');
	} //<--- End Method

	public function notifications()
	{

		$sql = DB::table('notifications')
			->select(DB::raw('
			notifications.id id_noty,
			notifications.type,
			notifications.created_at,
			users.id userId,
			users.username,
			users.name,
			users.avatar
			'))
			->leftjoin('users', 'users.id', '=', DB::raw('notifications.author'))
			->where('notifications.destination', '=',  auth()->user()->id)
			->where('notifications.trash', '=',  '0')
			->where('users.status', '=',  'active')
			->groupBy('notifications.id')
			->orderBy('notifications.id', 'DESC')
			->paginate(10);

		// Mark seen Notification
		Notifications::where('destination', auth()->user()->id)
			->update(array('status' => '1'));

		return view('users.notifications')->withSql($sql);
	} //<--- End Method

	public function notificationsDelete()
	{

		$notifications = Notifications::where('destination', auth()->user()->id)->get();

		if (isset($notifications)) {
			foreach ($notifications as $notification) {
				$notification->delete();
			}
		}

		return redirect('notifications');
	} //<--- End Method

	public function upload_avatar(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'photo' => [
				'required',
				'image',
				'dimensions:min_width=180,min_height=180',
				'max:' . $this->settings->file_size_allowed,
			]
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->getMessageBag()->toArray(),
			]);
		}

		$path = config('path.avatar');
		$photo = $request->file('photo');
		$extension = $photo->extension();
		$avatar = strtolower(
			auth()->user()->username . '-' .
				auth()->user()->id .
				time() .
				str_random(10) .
				'.' . $extension
		);

		try {
			// Create image manager instance with desired driver
			$manager = Image::read($photo);

			// Process the image
			$imgAvatar = $manager->cover(180, 180)->encodeByExtension($extension);

			// Store the image
			Storage::put($path . $avatar, $imgAvatar, 'public');

			// Delete old avatar if it's not the default
			if (auth()->user()->avatar != $this->settings->avatar) {
				Storage::delete($path . auth()->user()->avatar);
			}

			// Update user avatar in database
			auth()->user()->update(['avatar' => $avatar]);

			return response()->json([
				'success' => true,
				'avatar' => Storage::url($path . $avatar),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['photo' => 'Error processing image: ' . $e->getMessage()],
			]);
		}
	}

	public function upload_cover(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'photo' => [
				'required',
				'image',
				'dimensions:min_width=800,min_height=600',
				'max:' . $this->settings->file_size_allowed,
			]
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->getMessageBag()->toArray(),
			]);
		}

		try {
			$path = config('path.cover');
			$photo = $request->file('photo');
			$extension = $photo->extension();
			$cover = strtolower(
				auth()->user()->username . '-' .
					auth()->user()->id .
					time() .
					str_random(10) .
					'.' . $extension
			);

			$image = Image::read($photo);
			$maxWidth = ($image->width() < $image->height()) ? 800 : 1500;

			// Process the image
			$imgCover = $image->scale(width: $maxWidth)
				->encodeByExtension($extension);

			// Store the image
			Storage::put($path . $cover, $imgCover, 'public');

			// Delete old cover if it's not the default
			if (auth()->user()->cover != $this->settings->cover) {
				Storage::delete($path . auth()->user()->cover);
			}

			// Update user cover in database
			auth()->user()->update(['cover' => $cover]);

			return response()->json([
				'success' => true,
				'cover' => Storage::url($path . $cover),
			]);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'errors' => ['photo' => 'Error processing image: ' . $e->getMessage()],
			]);
		}
	}

	public function userLikes(Request $request)
	{
		$title = __('users.likes') . ' - ';

		// Images functionality removed for universal starter kit
		$images = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
			'path' => request()->url(),
		]);

		return view('users.likes', ['title' => $title, 'images' => $images]);
	} //<--- End Method

	public function followingFeed(Request $request)
	{

		$title = __('misc.feed') . ' - ';

		// Removed for universal starter kit - return empty paginated collection
		$images = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
			'path' => request()->url(),
		]);

		if (request()->ajax()) {
			return view('includes.images', ['images' => $images])->render();
		}

		return view('users.feed', ['title' => $title, 'images' => $images]);
	} //<--- End Method

	public function collections($slug, Request $request)
	{
		$user  = User::where('username', '=', $slug)
			->withCount(['followers', 'following'])
			->firstOrFail();
		$_title = $user->name ?: $user->username;
		$title  = $_title . ' - ' . __('misc.collections');

		if ($user->status == 'suspended') {
			return view('errors.user_suspended');
		}

		if (auth()->check()) {
			$AuthId = auth()->user()->id;
		} else {
			$AuthId = 0;
		}

		// Collections functionality removed for universal starter kit
		$collections = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
			'path' => request()->url(),
		]);

		if ($request->input('page') > $collections->lastPage()) {
			abort('404');
		}

		if (request()->ajax()) {
			return view('includes.collections-grid', ['data' => $collections])->render();
		}

		//<<<-- * Redirect the user real name * -->>>
		$uri = request()->path();
		$uriCanonical = $user->username . '/collections';

		if ($uri != $uriCanonical) {
			return redirect($uriCanonical);
		}

		if (auth()->check()) {
			// Follow Active
			$followActive = Followers::whereFollower(auth()->id())
				->where('following', $user->id)
				->where('status', '1')
				->first();

			if ($followActive) {
				$textFollow   = __('users.following');
				$icoFollow    = '-person-check';
				$activeFollow = 'btnFollowActive';
			} else {
				$textFollow   = __('users.follow');
				$icoFollow    = '-person-plus';
				$activeFollow = '';
			}
		}

		return view('users.profile', [
			'title' => $title,
			'collections' => $collections,
			'user' => $user,
			'textFollow' => $textFollow ?? null,
			'icoFollow' => $icoFollow ?? null,
			'activeFollow' => $activeFollow ?? null,
		]);
	} //<--- End Method

	/* COMMENTED OUT - Collections functionality removed for universal starter kit
	public function collectionDetail(Request $request)
	{
		// Collections functionality removed for universal starter kit
		abort(404);
	}
	END COMMENTED OUT */ //<--- End Method

	public function report(Request $request)
	{

		$data = UsersReported::firstOrNew(['user_id' => auth()->user()->id, 'id_reported' => $request->id]);

		if ($data->exists) {
			\Session::flash('noty_error', 'error');
			return redirect()->back();
		} else {

			$data->reason = $request->reason;
			$data->save();
			\Session::flash('noty_success', 'success');
			return redirect()->back();
		}
	} //<--- End Method

	/* COMMENTED OUT - Photos functionality removed for universal starter kit
	public function photosPending(Request $request)
	{
		// Photos functionality removed for universal starter kit
		$images = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
			'path' => request()->url(),
		]);

		return view('users.photos-pending', ['images' => $images]);
	}
	END COMMENTED OUT */ //<--- End Method

	public function invoice($id)
	{
		$data = Invoices::whereId($id)
			->whereStatus('paid')
			->firstOrFail();

		if ($data->user_id != auth()->id() && ! auth()->user()->isSuperAdmin()) {
			abort(404);
		}

		$taxes = TaxRates::whereIn('id', collect(explode('_', $data->taxes)))->get();
		$totalTaxes = ($data->amount * $taxes->sum('percentage') / 100);

		$totalAmount = ($data->amount + $data->transaction_fee + $totalTaxes);

		return view('users.invoice', [
			'data' => $data,
			'amount' => $data->amount,
			'percentageApplied' => $data->percentage_applied,
			'transactionFee' => $data->transaction_fee,
			'totalAmount' => $totalAmount,
			'taxes' => $taxes
		]);
	}

	public function myReferrals()
	{
		$transactions = ReferralTransactions::whereReferredBy(auth()->id())
			->orderBy('id', 'desc')
			->paginate(20);

		return view('users.referrals', ['transactions' => $transactions]);
	} //<--- End Method

	public function subscription()
	{
		$subscription  = auth()->user()->mySubscription()->latest()->first();
		$subscriptions = auth()->user()->mySubscription()->latest()->paginate(10);

		return view('users.subscription')->with([
			'subscription' => $subscription,
			'subscriptions' => $subscriptions
		]);
	}
}
