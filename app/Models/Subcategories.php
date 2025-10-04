<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategories extends Model
{
	protected $guarded = [];
	public $timestamps = false;

	public function category()
	{
		return $this->belongsTo(Categories::class);
	}
}
