<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AdminSettings;
use App\Models\UsersReported;
// Images-related models removed for universal starter kit
use App\Models\Like;
use App\Models\Notifications;
use App\Models\Query;
use App\Models\Followers;
use App\Models\Comments;
use App\Helper;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image;

class AjaxController extends Controller {

	public function __construct( AdminSettings $settings) {
		$this->settings = $settings::first();
	}

	public function like(Request $request) {
		// Images functionality removed for universal starter kit
		return '0';
	}//<---- End Method

	public function follow(Request $request)
	{
		$findUser = User::whereId($request->id)
				->where('id', '<>', auth()->id())
				->whereStatus('active')
				->first();

		if (! $findUser) {
			return response()->json([
				'status' => false,
			]);
		}

		 $user = Followers::firstOrNew(['follower' => auth()->id(), 'following' => $request->id]);

		if ($user->exists) {

			   $notifications = Notifications::where('destination',$request->id)
			   ->where('author',auth()->id())
			   ->where('target',auth()->id())
			   ->where('type','1')
			   ->first();

				// IF ACTIVE DELETE FOLLOW
				if ($user->status == '1') {
					$user->status = '0';
					$user->update();

				if (isset($notifications)) {
					// DELETE NOTIFICATION
					$notifications->status = '1';
					$notifications->update();
				}

				// ELSE ACTIVE AGAIN
				} else {
					$user->status = '1';
					$user->update();

					if (isset($notifications)) {
					// ACTIVE NOTIFICATION
					$notifications->status = '0';
					$notifications->update();
					}
				}

		} else {

			// INSERT
			$user->save();

			// Send Notification //destination, author, type, target
			if ($request->id != auth()->id()) {
				Notifications::send( $request->id, auth()->id(), '1', auth()->id() );
			}

		}
			return response()->json([
				'status' => true,
			]);
	}//<---- End Method

	// Notifications
	public function notifications() {

		if (auth()->check()) {

		   if (request()->ajax()) {

			$notifications = Notifications::where('destination', auth()->id())->where('status','0')->count();

			return response()->json(['notifications' => $notifications]);

		   } else {
				return response()->json(['error' => 1]);
			}
	  } else {
				return response()->json(['error' => 1]);
			}

   }//<---- * End Method

   public function users() {

	 $data = Query::users();

	 return view('ajax.users-ajax')->with($data)->render();

	}//<---- End Method

	public function search() {

	 $images = Query::searchImages();

	 return view('ajax.images-ajax')->with($images)->render();

	}//<---- End Method

	public function latest() {

	 $images = Query::latestImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function featured() {

	 $images = Query::featuredImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function popular() {

	 $images = Query::popularImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function commented() {

	 $images = Query::commentedImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function viewed() {

	 $images = Query::viewedImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function downloads() {

	 $images = Query::downloadsImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function category( Request $request ) {

	 $slug = trim($request->slug);

	 $images = Query::categoryImages($slug);

	 return view('ajax.images-ajax')->with($images)->render();

	}//<---- End Method

	public function tags( Request $request ) {

		 $slug = trim($request->q);

		 $images = Query::tagsImages($slug);

		 return view('ajax.images-ajax')->with($images)->render();

	}//<---- End Method

	public function camera( Request $request ) {

		 $slug = trim($request->q);

		 $images = Query::camerasImages($slug);

		 return view('ajax.images-ajax')->with($images)->render();

	}//<---- End Method

	public function colors( Request $request ) {

		 $slug = trim($request->q);

		 $images = Query::colorsImages($slug);

		 return view('ajax.images-ajax')->with($images)->render();

	}//<---- End Method

	public function userImages( Request $request ) {

		 $id = $request->id;

		 $images = Query::userImages($id);

		 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

	public function comments( Request $request ) {

		 $id = $request->photo;

		 $comments_sql = Comments::where('images_id', $id)->where('status','1')->orderBy('date', 'desc')->paginate(10);

		 return view('includes.comments', ['comments_sql' => $comments_sql])->render();

	}//<---- End Method

	public function premium() {

	 $images = Query::premiumImages();

	 return view('ajax.images-ajax',['images' => $images])->render();

	}//<---- End Method

}
