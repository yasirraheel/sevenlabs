<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plans;
use App\Models\AdminSettings;
use App\Helper;

class PlansController extends Controller
{
  use Traits\FunctionsTrait;

  public function __construct(AdminSettings $settings)
	{
		$this->settings = $settings::first();
	}

  public function show()
  {
    $plans = Plans::orderBy('id', 'desc')->get();

    return view('admin.plans')->withPlans($plans);

  }//<--- End Method

  public function store(Request $request)
  {
    $validated = $request->validate([
        'name' => 'required|max:100',
        'price' => 'required|numeric|min:1',
        'price_year' => 'required|numeric|min:1',
        'credits' => 'required|numeric|min:1',
        'duration' => 'required|in:month,year',
    ]);

    $plan = new Plans();
    $plan->plan_id = Helper::strRandom(10);
    $plan->name = $request->name;
    $plan->price = $request->price;
    $plan->price_year = $request->price_year;
    $plan->credits = $request->credits;
    $plan->duration = $request->duration;
    $plan->unused_credits_rollover = $request->unused_credits_rollover ?? false;
    $plan->save();

    return redirect('panel/admin/plans')
        ->withSuccessMessage(__('admin.success_add'));

  }//<--- End Method

  public function edit($id)
  {
    $plan = Plans::findOrFail($id);

    return view('admin.edit-plan')->withPlan($plan);
  }//<--- End Method

  public function update(Request $request)
  {
    $plan = Plans::findOrFail($request->id);

    $validated = $request->validate([
        'name' => 'required|max:100',
        'price' => 'required|numeric|min:1',
        'price_year' => 'required|numeric|min:1',
        'credits' => 'required|numeric|min:1',
        'duration' => 'required|in:month,year',
    ]);

    $plan->name = $request->name;
    $plan->price = $request->price;
    $plan->price_year = $request->price_year;
    $plan->credits = $request->credits;
    $plan->duration = $request->duration;
    $plan->unused_credits_rollover = $request->unused_credits_rollover ?? false;
    $plan->status = $request->status ?? false;
    $plan->save();

    return redirect('panel/admin/plans')
        ->withSuccessMessage(__('misc.success_update'));

  }//<--- End Method
}
