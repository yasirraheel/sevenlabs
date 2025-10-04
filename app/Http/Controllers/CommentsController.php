<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Models\User;
use App\Models\AdminSettings;
use App\Models\Comments;
use App\Models\CommentsLikes;
use App\Models\Notifications;
// Images model removed for universal starter kit
use App\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CommentsController extends Controller {

	 public function __construct( AdminSettings $settings, Request $request) {
		$this->settings = $settings::first();
		$this->request = $request;
	}

	 protected function validator(array $data) {

    	Validator::extend('ascii_only', function($attribute, $value, $parameters){
    		return !preg_match('/[^x00-x7F\-]/i', $value);
		});

			return Validator::make($data, [
	        	'comment' =>  'required|max:'.$this->settings->comment_length.'|min:2',
	        ]);

    }

	 /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
	 public function store( Request $request ) {

		 $input = $request->all();

	     $validator = $this->validator($input);

	   // Images functionality removed for universal starter kit
	   // For now, we'll allow comments on any content type
	   $image = (object) ['id' => $request->image_id, 'user_id' => 1]; // Placeholder

	    if ($validator->fails()) {
	        return response()->json([
			        'success' => false,
			        'errors' => $validator->getMessageBag()->toArray(),
			    ]);
	    }

		$sql            = new Comments;
		$sql->reply     = trim(Helper::checkTextDb($request->comment));
		$sql->images_id = $request->image_id; // Keep for backward compatibility
		$sql->user_id   = auth()->user()->id;
		$sql->save();

		$idComment = $sql->id;

		/*------* SEND NOTIFICATION * ------*/

		if (auth()->user()->id != $image->user_id) {
			// Send Notification //destination, author, type, target
			Notifications::send( $image->user_id, auth()->user()->id, '3', $image->id );
		}

		if (auth()->user()->name != '') {
			$nameUser = auth()->user()->name;
		} else {
			$nameUser = auth()->user()->username;
		}

		$data = view('includes.comments', [
				 'comments_sql' => Comments::whereId($sql->id)->paginate(1)
				 ])->render();

		return response()->json([
			        'success' => true,
			        'total' => '0', // Comments count removed
			        'data' => $data
			    ]);


	}//<--- End Method

	public function like(Request $request) {

		$id = $request->comment_id;

	    $comment = Comments::where('id', $id)->where('status','1')->first();

		$comment_like = CommentsLikes::where('user_id', '=', auth()->user()->id)
		->where('comment_id', '=', $id)->first();

		if( isset( $comment_like->id ) ){

			if( $comment_like->status == '1' ) {
				//UNLIKE
				$comment_like->status = '0';
				$comment_like->save();

				$comment_count = CommentsLikes::where('comment_id', '=', $id)->where('status','1')->count();

				return response()->json( array ( 'success' => true, 'type' => 'unlike', 'count' => $comment_count ) );
			} else {
				//UNLIKE
				$comment_like->status = '1';
				$comment_like->save();

				$comment_count = CommentsLikes::where('comment_id', '=', $id)->where('status','1')->count();

				return response()->json( array ( 'success' => true, 'type' => 'like', 'count' => $comment_count ) );
			}

		} else {
			$like                      = new CommentsLikes;
			$like->user_id        = auth()->user()->id;
			$like->comment_id = $id;
			$like->save();

			// SEND NOTIFICATION
			if( auth()->user()->id != $comment->user_id ) {
				Notifications::send( $comment->user_id, auth()->user()->id, '4', $comment->images_id );
			}

			$comment_count = CommentsLikes::where('comment_id', '=', $id)->where('status','1')->count();

			return response()->json( array ( 'success' => true, 'type' => 'like', 'count' => $comment_count ) );
		}

	}//<--- END METHOD

	public function getLikes(Request $request) {

				$comment_id = $request->comment_id;

				$_array   = array();

				$data = CommentsLikes::where('comment_id', $comment_id)->where('status', '1')->get();

				if( !isset( $data ) ){
					return false;
					exit;
				}

				foreach ($data as $key) {
					$_array[] = '<li><a href="'.url($key->user()->username).'" class="showTooltip" data-toggle="tooltip" data-placement="left" title="'.$key->user()->username.'">
					<img src="'.Storage::url(config('path.avatar').$key->user()->avatar).'" class="img-circle" width="25">
					</a></li>';
				}
				return $_array;

		}//<--- END METHOD


	/**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
	public function destroy(Request $request)
	{
	  $comment_id = $request->comment_id;
		$comment = Comments::whereId($comment_id)->first();

		if ($comment->user_id == auth()->id()) {

			// Delete Notification
			Notifications::where('author', $comment->user_id)
			->where('target', $comment->images_id)
			->where('created_at', $comment->date)
			->update([
				'trash' => '1',
				'status' => '1'
			]);

			$comment->delete();

			return response()->json(['success' => true]);

		} else {
			return response()->json([
				'success' => false,
				'error' => trans('misc.error')
			]);
		}

	}//<--- End Method


}
