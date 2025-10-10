<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Plans;
use App\Models\Deposits;
use App\Models\TaxRates;
use App\Models\Languages;
use App\Models\Categories;
use App\Models\AdminSettings;
use App\Models\UsersReported;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot()
	{
		try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
			return false;
        }

		$settings = AdminSettings::first();
		$categoriesCount = Categories::count();
		$categoriesMain = Categories::where('mode', 'on')->orderBy('name')->take(5)->get();
		$languages = Languages::orderBy('name')->get();
		$taxRatesCount = TaxRates::whereStatus('1')->count();
		$userCount = User::whereStatus('active')->count();
		$plansActive = Plans::whereStatus('1')->count();
		$depositsPendingCount = Deposits::selectRaw('COUNT(id) as total')->whereStatus('pending')->pluck('total')->first();
		$withdrawalsPendingCount = 0; // Withdrawals functionality removed
		$usersReported = UsersReported::selectRaw('COUNT(id) as total')->pluck('total')->first();

		// Universal starter kit - set default values for removed stock photo functionality
		$downloadsCount = 0;
		$imagesCount = 0;

		view()->share(compact(
			'settings',
			'categoriesCount',
			'categoriesMain',
			'languages',
			'taxRatesCount',
			'userCount',
			'plansActive',
			'depositsPendingCount',
			'withdrawalsPendingCount',
			'usersReported',
			'downloadsCount',
			'imagesCount'
		));
	}
}
