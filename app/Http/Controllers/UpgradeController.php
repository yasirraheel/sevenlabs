<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Categories;
use App\Models\AdminSettings;
use App\Models\PaymentGateways;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class UpgradeController extends Controller
{

	public function __construct(AdminSettings $settings, Images $images, Collections $collections, User $user, Categories $categories)
	{
		$this->user         = $user::first();
		$this->settings     = $settings::first();
		$this->images       = $images::first();
		$this->collections  = $collections::first();
		$this->categories   = $categories::first();
	}

	/**
	 * Move a file
	 *
	 */
	private static function moveFile($file, $newFile, $copy)
	{
		if (File::exists($file) && $copy == false) {
			File::delete($newFile);
			File::move($file, $newFile);
		} else if (File::exists($newFile) && isset($copy)) {
			File::copy($newFile, $file);
		}
	}

	/**
	 * Copy a directory
	 *
	 */
	private static function moveDirectory($directory, $destination, $copy)
	{
		if (File::isDirectory($directory) && $copy == false) {
			File::moveDirectory($directory, $destination);
		} else if (File::isDirectory($destination) && isset($copy)) {
			File::copyDirectory($destination, $directory);
		}
	}

	public function update($version)
	{
		$DS = DIRECTORY_SEPARATOR;

		$ROOT = base_path() . $DS;
		$APP = app_path() . $DS;
		$MODELS = app_path('Models') . $DS;
		$JOBS = app_path('Jobs') . $DS;
		$NOTIFICATIONS = app_path('Notifications') . $DS;
		$CONTROLLERS = app_path('Http' . $DS . 'Controllers') . $DS;
		$CONTROLLERS_AUTH = app_path('Http' . $DS . 'Controllers' . $DS . 'Auth') . $DS;
		$TRAITS = app_path('Http' . $DS . 'Controllers' . $DS . 'Traits') . $DS;
		$PROVIDERS = app_path('Providers') . $DS;
		$MIDDLEWARE = app_path('Http' . $DS . 'Middleware') . $DS;

		$CONFIG = config_path() . $DS;

		$ROUTES = base_path('routes') . $DS;

		$PUBLIC_JS = public_path('js') . $DS;
		$PUBLIC_CSS = public_path('css') . $DS;
		$PUBLIC_IMG = public_path('img') . $DS;
		$PUBLIC_FONTS_BOOTSTRAP = public_path('webfonts' . $DS . 'bootstrap') . $DS;

		$VIEWS = resource_path('views') . $DS;
		$VIEWS_ADMIN = resource_path('views' . $DS . 'admin') . $DS;
		$VIEWS_AJAX = resource_path('views' . $DS . 'ajax') . $DS;
		$VIEWS_AUTH = resource_path('views' . $DS . 'auth') . $DS;
		$VIEWS_AUTH_PASS = resource_path('views' . $DS . 'auth' . $DS . 'passwords') . $DS;
		$VIEWS_DASHBOARD = resource_path('views' . $DS . 'dashboard') . $DS;
		$VIEWS_DEFAULT = resource_path('views' . $DS . 'default') . $DS;
		$VIEWS_EMAILS = resource_path('views' . $DS . 'emails') . $DS;
		$VIEWS_ERRORS = resource_path('views' . $DS . 'errors') . $DS;
		$VIEWS_IMAGES = resource_path('views' . $DS . 'images') . $DS;
		$VIEWS_INCLUDES = resource_path('views' . $DS . 'includes') . $DS;
		$VIEWS_INDEX = resource_path('views' . $DS . 'index') . $DS;
		$VIEWS_INSTALLER = resource_path('views' . $DS . 'installer') . $DS;
		$VIEWS_LAYOUTS = resource_path('views' . $DS . 'layouts') . $DS;
		$VIEWS_PAGES = resource_path('views' . $DS . 'pages') . $DS;
		$VIEWS_USERS = resource_path('views' . $DS . 'users') . $DS;

		$upgradeDone = '<h2 style="text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #4BBA0B;">' . trans('admin.upgrade_done') . ' <a style="text-decoration: none; color: #F50;" href="' . url('/') . '">' . trans('error.go_home') . '</a></h2>';

		//<<---- Version 1.3 ----->>
		if ($version == '1.3') {

			if (isset($this->settings->google_adsense_index)) {
				return redirect('/');
			} else {

				Schema::table('admin_settings', function ($table) {
					$table->text('google_adsense_index')->after('min_width_height_image');
				});

				return $upgradeDone;
			}
		} //<<---- Version 1.3 ----->>

		//<<---- Version 1.6 ----->>
		if ($version == '1.6') {

			// Create Table languages
			if (!Schema::hasTable('languages')) {
				Schema::create('languages', function ($table) {
					$table->increments('id');
					$table->string('name', 100);
					$table->string('abbreviation', 32);
				});

				if (Schema::hasTable('languages')) {
					DB::table('languages')->insert(
						array('name' => 'English', 'abbreviation' => 'en')
					);
				}
			} // <<--- End Create Table languages

			// Add Instagram
			if (!Schema::hasColumn('users', 'instagram')) {
				Schema::table('users', function ($table) {
					$table->string('instagram', 200)->after('authorized_to_upload');
				});
			} // <<--- End Add Instagram

			// Add Link to Pages Terms and Privacy
			if (!Schema::hasColumn('admin_settings', 'link_terms', 'link_privacy')) {
				Schema::table('admin_settings', function ($table) {
					$table->string('link_terms', 200)->after('google_adsense_index');
					$table->string('link_privacy', 200)->after('google_adsense_index');
				});
			} // <<--- End Add Link to Pages Terms and Privacy


			return $upgradeDone;
		} //<<---- Version 1.6 ----->>

		//<<---- Version 2.0 ----->>
		if ($version == '2.0') {

			// Add Fields in Users Table
			if (!Schema::hasColumn('users', 'funds', 'balance', 'payment_gateway', 'bank')) {
				Schema::table('users', function ($table) {
					$table->unsignedInteger('funds');
					$table->decimal('balance', 10, 2);
					$table->string('payment_gateway', 50);
					$table->text('bank');
				});
			} // <<-- Add Fields in Users Table

			// Add Fields in Images Table
			if (!Schema::hasColumn('images', 'price', 'item_for_sale', 'funds')) {
				Schema::table('images', function ($table) {
					$table->unsignedInteger('price');
					$table->enum('item_for_sale', ['free', 'sale'])->default('free');
				});
			} // <<--- End Add Fields in Images Table

			// Add Fields in AdminSettings
			if (!Schema::hasColumn(
				'admin_settings',
				'paypal_sandbox',
				'paypal_account',
				'fee_commission',
				'stripe_secret_key',
				'stripe_public_key',
				'max_deposits_amount',
				'min_deposits_amount',
				'min_sale_amount',
				'max_sale_amount',
				'amount_min_withdrawal',
				'enable_paypal',
				'enable_stripe',
				'currency_position',
				'currency_symbol',
				'currency_code',
				'handling_fee'

			)) {

				Schema::table('admin_settings', function ($table) {
					$table->enum('paypal_sandbox', ['true', 'false'])->default('true');
					$table->string('paypal_account', 200);
					$table->unsignedInteger('fee_commission');

					$table->string('stripe_secret_key', 200);
					$table->string('stripe_public_key', 200);

					$table->unsignedInteger('max_deposits_amount');
					$table->unsignedInteger('min_deposits_amount');
					$table->unsignedInteger('min_sale_amount');
					$table->unsignedInteger('max_sale_amount');
					$table->unsignedInteger('amount_min_withdrawal');

					$table->enum('enable_paypal', ['0', '1'])->default('0');
					$table->enum('enable_stripe', ['0', '1'])->default('0');

					$table->enum('currency_position', ['left', 'right'])->default('left');
					$table->string('currency_symbol', 200);
					$table->string('currency_code', 200);
					$table->unsignedInteger('handling_fee');
				});
			} // <<--- End Add Fields in AdminSettings

			// Create table Deposits
			if (!Schema::hasTable('deposits')) {

				Schema::create('deposits', function ($table) {

					$table->engine = 'InnoDB';
					$table->increments('id');
					$table->unsignedInteger('user_id');
					$table->string('txn_id', 200);
					$table->unsignedInteger('amount');
					$table->string('payment_gateway', 100);
					$table->timestamp('date');
				});
			} // <<< --- Create table Deposits

			// Create table Purchases
			if (!Schema::hasTable('purchases')) {

				Schema::create('purchases', function ($table) {

					$table->engine = 'InnoDB';
					$table->increments('id');
					$table->unsignedInteger('images_id');
					$table->unsignedInteger('user_id');
					$table->unsignedInteger('price');
					$table->timestamp('date');
					$table->enum('approved', ['0', '1'])->default('1');
					$table->decimal('earning_net_seller', 10, 2);
					$table->decimal('earning_net_admin', 10, 2);
				});
			} // <<< --- Create table Purchases

			// Create table Purchases
			if (!Schema::hasTable('withdrawals')) {

				Schema::create('withdrawals', function ($table) {

					$table->engine = 'InnoDB';
					$table->increments('id');
					$table->unsignedInteger('user_id');
					$table->enum('status', ['pending', 'paid'])->default('pending');
					$table->string('amount', 50);
					$table->timestamp('date');
					$table->string('gateway', 100);
					$table->text('account');
					$table->timestamp('date_paid')->default('0000-00-00 00:00:00');
				});
			} // <<< --- Create table Purchases

			return $upgradeDone;
		} //<<---- Version 2.0 ----->>

		//<<---- Version 2.3 ----->>
		if ($version == '2.3') {

			// AdminSettings
			if (!Schema::hasColumn(
				'admin_settings',
				'sell_option',
				'ip'
			)) {

				Schema::table('admin_settings', function ($table) {
					$table->enum('sell_option', ['on', 'off'])->default('on');
				});
			} // Schema hasColumn AdminSettings

			// User
			if (!Schema::hasColumn('users', 'ip')) {

				Schema::table('users', function ($table) {
					$table->string('ip', 30);
				});
			} // Schema hasColumn User
			return $upgradeDone;
		} //<<---- Version 2.3 ----->>

		//<------------------------ Version 2.5
		if ($version == '2.5') {

			// Create table payment_gateways
			if (!Schema::hasTable('payment_gateways')) {

				Schema::create('payment_gateways', function ($table) {

					$table->engine = 'InnoDB';

					$table->increments('id');
					$table->string('name', 50);
					$table->string('type');
					$table->enum('enabled', ['1', '0'])->default('1');
					$table->enum('sandbox', ['true', 'false'])->default('true');
					$table->decimal('fee', 3, 1);
					$table->decimal('fee_cents', 2, 2);
					$table->string('email', 80);
					$table->string('token', 200);
					$table->string('key', 255);
					$table->string('key_secret', 255);
					$table->text('bank_info');
				});

				\DB::table('payment_gateways')->insert([
					[
						'name' => 'PayPal',
						'type' => 'normal',
						'enabled' => $this->settings->enable_paypal,
						'fee' => 5.4,
						'fee_cents' => 0.30,
						'email' => $this->settings->paypal_account,
						'key' => '',
						'key_secret' => '',
						'bank_info' => '',
						'token' => '02bGGfD9bHevK3eJN06CdDvFSTXsTrTG44yGdAONeN1R37jqnLY1PuNF0mJRoFnsEygyf28yePSCA1eR0alQk4BX89kGG9Rlha2D2KX55TpDFNR5o774OshrkHSZLOFo2fAhHzcWKnwsYDFKgwuaRg',
					],
					[
						'name' => 'Stripe',
						'type' => 'card',
						'enabled' => $this->settings->enable_stripe,
						'fee' => 2.9,
						'fee_cents' => 0.30,
						'email' => '',
						'key' => $this->settings->stripe_public_key,
						'key_secret' => $this->settings->stripe_secret_key,
						'bank_info' => '',
						'token' => 'asfQSGRvYzS1P0X745krAAyHeU7ZbTpHbYKnxI2abQsBUi48EpeAu5lFAU2iBmsUWO5tpgAn9zzussI4Cce5ZcANIAmfBz0bNR9g3UfR4cserhkJwZwPsETiXiZuCixXVDHhCItuXTPXXSA6KITEoT',
					]
				]);
			} // End create table payment_gateways

			return $upgradeDone;
		} //<---------------------- Version 2.5

		//<------------------------ Version 2.7
		if ($version == '2.7') {

			// Insert on AdminSettings
			if (!Schema::hasColumn('admin_settings', 'show_images_index', 'file_size_allowed_vector', '')) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('show_images_index', ['latest', 'featured', 'both'])->default('latest');
					$table->enum('show_watermark', ['1', '0'])->default('1');
					$table->unsignedInteger('file_size_allowed_vector')->default(1024);
				});
			}

			// Insert on Images
			if (!Schema::hasColumn('images', 'vector')) {
				Schema::table('images', function ($table) {
					$table->string('vector', 3);
				});
			}

			if (!file_exists('public/uploads/files')) {
				mkdir('public/uploads/files', 0777, true);
			}

			return $upgradeDone;
		} //<---------------------- Version 2.7

		//<------------------------ Version 3.2
		if ($version == '3.2') {

			// Insert on Images
			if (!Schema::hasColumn('purchases', 'type')) {
				Schema::table('purchases', function ($table) {
					$table->string('type', 25);
				});

				if (Schema::hasColumn('purchases', 'type')) {

					foreach (Purchases::all() as $key) {
						Purchases::whereId($key->id)->update(['date' => $key->date, 'type' => 'large']);
					}
				}
			}

			return $upgradeDone;
		} //<---------------------- Version 3.2

		//<------------------------ Version 3.3
		if ($version == '3.3') {

			// Add Link to License
			if (!Schema::hasColumn('admin_settings', 'link_license', 'decimal_format', 'version')) {
				Schema::table('admin_settings', function ($table) {
					$table->string('link_license', 200);
					$table->enum('decimal_format', ['comma', 'dot'])->default('dot');
					$table->string('version', 5);
				});

				if (Schema::hasColumn('admin_settings', 'version')) {
					AdminSettings::whereId(1)->update([
						'version' => '3.3'
					]);
				}
			} // <<--- End Add Link to License

			// Insert on Purchases
			if (!Schema::hasColumn('purchases', 'license', 'purchase_code', 'order_id')) {
				Schema::table('purchases', function ($table) {
					$table->string('license', 25);
					$table->string('order_id', 25);
					$table->string('purchase_code', 40);
				});

				if (Schema::hasColumn('purchases', 'license', 'purchase_code', 'order_id')) {

					foreach (Purchases::all() as $key) {
						Purchases::whereId($key->id)->update([
							'date' => $key->date,
							'license' => 'regular',
							'purchase_code' => implode('-', str_split(substr(strtolower(md5(time() . mt_rand(1000, 9999))), 0, 27), 5)),
							'order_id' => substr(strtolower(md5(microtime() . mt_rand(1000, 9999))), 0, 15),
						]);
					}
				}
			} // Insert on Purchases

			return $upgradeDone;
		} //<---------------------- Version 3.3

		//<<---- Version 3.4 ----->>
		if ($version == '3.4') {

			if ($this->settings->version == '3.4') {
				return redirect('/');
			}

			if ($this->settings->version != '3.3' || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version 3.3</h2>";
			}

			file_put_contents(
				'.env',
				"\nFILESYSTEM_DRIVER=default\n\nWAS_ACCESS_KEY_ID=\nWAS_SECRET_ACCESS_KEY=\nWAS_DEFAULT_REGION=\nWAS_BUCKET=\n\nDOS_ACCESS_KEY_ID=\nDOS_SECRET_ACCESS_KEY=\nDOS_DEFAULT_REGION=\nDOS_BUCKET=\n",
				FILE_APPEND
			);

			//============ Starting moving files...
			$path           = "v$version/";
			$pathAdmin      = "v$version/admin/";
			$copy           = false;

			//============== Files ================//
			$file1 = $path . 'Helper.php';
			$file2 = $path . 'path.php';
			$file3 = $path . 'filesystems.php';
			$file4 = $path . 'ImagesController.php';
			$file5 = $path . 'UserController.php';
			$file6 = $path . 'AdminController.php';
			$file7 = $path . 'AdminUserController.php';
			$file8 = $path . 'HomeController.php';
			$file9 = $path . 'AjaxController.php';
			$file10 = $path . 'CommentsController.php';
			$file11 = $path . 'userTraits.php';
			$file12 = $path . 'functions.js';
			$file13 = $pathAdmin . 'dashboard.blade.php';
			$file14 = $pathAdmin . 'purchases.blade.php';
			$file15 = $pathAdmin . 'images.blade.php';
			$file16 = $pathAdmin . 'edit-image.blade.php';
			$file17 = $pathAdmin . 'members.blade.php';
			$file18 = $pathAdmin . 'layout.blade.php';
			$file19 = $pathAdmin . 'edit-member.blade.php';
			$file20 = $path . 'dashboard.blade.php';
			$file21 = $path . 'layout.blade.php';
			$file22 = $path . 'photos.blade.php';
			$file23 = $path . 'purchases.blade.php';
			$file24 = $path . 'sales.blade.php';
			$file25 = $path . 'cameras.blade.php';
			$file26 = $path . 'category.blade.php';
			$file27 = $path . 'colors.blade.php';
			$file28 = $path . 'search.blade.php';
			$file29 = $path . 'tags-show.blade.php';

			$file30 = $path . 'edit.blade.php';
			$file31 = $path . 'show.blade.php';
			$file32 = $path . 'upload.blade.php';

			$file33 = $path . 'collections.blade.php';
			$file34 = $path . 'comments.blade.php';
			$file35 = $path . 'images.blade.php';
			$file36 = $path . 'navbar.blade.php';
			$file37 = $path . 'users.blade.php';

			$file38 = $path . 'explore.blade.php';
			$file39 = $path . 'profile.blade.php';

			$file40 = $path . 'smartphoto.min.css';
			$file41 = $path . 'smartphoto.min.js';

			//============== Paths ================//
			$path1 = app_path('Helper.php');
			$path2 = config_path('path.php');
			$path3 = config_path('filesystems.php');

			$path4 = app_path('Http/Controllers/ImagesController.php');
			$path5 = app_path('Http/Controllers/UserController.php');
			$path6 = app_path('Http/Controllers/AdminController.php');
			$path7 = app_path('Http/Controllers/AdminUserController.php');
			$path8 = app_path('Http/Controllers/HomeController.php');
			$path9 = app_path('Http/Controllers/AjaxController.php');
			$path10 = app_path('Http/Controllers/CommentsController.php');
			$path11 = app_path('Http/Controllers/Traits/userTraits.php');
			$path12 = public_path('js/functions.js');

			$path13 = resource_path('views/admin/dashboard.blade.php');
			$path14 = resource_path('views/admin/purchases.blade.php');
			$path15 = resource_path('views/admin/images.blade.php');
			$path16 = resource_path('views/admin/edit-image.blade.php');
			$path17 = resource_path('views/admin/members.blade.php');
			$path18 = resource_path('views/admin/layout.blade.php');
			$path19 = resource_path('views/admin/edit-member.blade.php');

			$path20 = resource_path('views/dashboard/dashboard.blade.php');
			$path21 = resource_path('views/dashboard/layout.blade.php');
			$path22 = resource_path('views/dashboard/photos.blade.php');
			$path23 = resource_path('views/dashboard/purchases.blade.php');
			$path24 = resource_path('views/dashboard/sales.blade.php');

			$path25 = resource_path('views/default/cameras.blade.php');
			$path26 = resource_path('views/default/category.blade.php');
			$path27 = resource_path('views/default/colors.blade.php');
			$path28 = resource_path('views/default/search.blade.php');
			$path29 = resource_path('views/default/tags-show.blade.php');

			$path30 = resource_path('views/images/edit.blade.php');
			$path31 = resource_path('views/images/show.blade.php');
			$path32 = resource_path('views/images/upload.blade.php');

			$path33 = resource_path('views/includes/collections.blade.php');
			$path34 = resource_path('views/includes/comments.blade.php');
			$path35 = resource_path('views/includes/images.blade.php');
			$path36 = resource_path('views/includes/navbar.blade.php');
			$path37 = resource_path('views/includes/users.blade.php');

			$path38 = resource_path('views/index/explore.blade.php');
			$path39 = resource_path('views/users/profile.blade.php');

			$path40 = public_path('css/smartphoto.min.css');
			$path41 = public_path('js/smartphoto.min.js');

			//============== Moving Files ================//
			$this->moveFile($file1, $path1, $copy);
			$this->moveFile($file2, $path2, $copy);
			$this->moveFile($file3, $path3, $copy);
			$this->moveFile($file4, $path4, $copy);
			$this->moveFile($file5, $path5, $copy);
			$this->moveFile($file6, $path6, $copy);
			$this->moveFile($file7, $path7, $copy);
			$this->moveFile($file8, $path8, $copy);
			$this->moveFile($file9, $path9, $copy);
			$this->moveFile($file10, $path10, $copy);
			$this->moveFile($file11, $path11, $copy);
			$this->moveFile($file12, $path12, $copy);
			$this->moveFile($file13, $path13, $copy);
			$this->moveFile($file14, $path14, $copy);
			$this->moveFile($file15, $path15, $copy);
			$this->moveFile($file16, $path16, $copy);
			$this->moveFile($file17, $path17, $copy);
			$this->moveFile($file18, $path18, $copy);
			$this->moveFile($file19, $path19, $copy);
			$this->moveFile($file20, $path20, $copy);
			$this->moveFile($file21, $path21, $copy);
			$this->moveFile($file22, $path22, $copy);
			$this->moveFile($file23, $path23, $copy);
			$this->moveFile($file24, $path24, $copy);
			$this->moveFile($file25, $path25, $copy);
			$this->moveFile($file26, $path26, $copy);
			$this->moveFile($file27, $path27, $copy);
			$this->moveFile($file28, $path28, $copy);
			$this->moveFile($file29, $path29, $copy);
			$this->moveFile($file30, $path30, $copy);
			$this->moveFile($file31, $path31, $copy);
			$this->moveFile($file32, $path32, $copy);
			$this->moveFile($file33, $path33, $copy);
			$this->moveFile($file34, $path34, $copy);
			$this->moveFile($file35, $path35, $copy);
			$this->moveFile($file36, $path36, $copy);
			$this->moveFile($file37, $path37, $copy);
			$this->moveFile($file38, $path38, $copy);
			$this->moveFile($file39, $path39, $copy);
			$this->moveFile($file40, $path40, $copy);
			$this->moveFile($file41, $path41, $copy);

			//============ End Moving Files ===============//

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			return $upgradeDone;
		} //<<---- Version 3.4 ----->>

		//<<---- Version 3.5 ----->>
		if ($version == '3.5') {

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != '3.4' || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version 3.4</h2>";
			}

			// Add title_length
			if (!Schema::hasColumn('admin_settings', 'title_length')) {
				Schema::table('admin_settings', function ($table) {
					$table->unsignedInteger('title_length');
				});

				if (Schema::hasColumn('admin_settings', 'title_length')) {
					AdminSettings::whereId(1)->update([
						'title_length' => 50
					]);
				}
			} // <<--- End Add title_length

			DB::table('reserved')->insert(
				[
					['name' => 'core'],
					['name' => 'update']
				]
			);

			$replace = "Route::get('/logout', 'Auth\LoginController@logout');\nRoute::get('contact','HomeController@contact');\nRoute::post('contact','HomeController@contactStore');";

			$fileConfig = 'routes/web.php';
			file_put_contents(
				$fileConfig,
				str_replace(
					"Route::get('/logout', 'Auth\LoginController@logout');",
					$replace,
					file_get_contents($fileConfig)
				)
			);


			//============ Starting moving files...
			$path           = "v$version/";
			$pathAdmin      = "v$version/admin/";
			$copy           = false;

			//============== Files ================//
			$file1 = $path . 'Helper.php';
			$file2 = $path . 'AdminController.php';
			$file3 = $path . 'ImagesController.php';
			$file4 = $path . 'Upload.php';
			$file5 = $pathAdmin . 'layout.blade.php';
			$file6 = $pathAdmin . 'limits.blade.php';
			$file7 = $path . 'dropzone.min.css';
			$file8 = $path . 'dropzone.min.js';
			$file9 = $path . 'HomeController.php';
			$file10 = $path . 'StripeController.php';
			$file11 = $path . 'contact.blade.php';
			$file12 = $path . 'contact-email.blade.php';
			$file13 = $path . 'add-funds.blade.php';
			$file14 = $path . 'footer.blade.php';

			//============== Paths ================//
			$path1 = app_path('Helper.php');
			$path2 = app_path('Http/Controllers/AdminController.php');
			$path3 = app_path('Http/Controllers/ImagesController.php');
			$path4 = app_path('Http/Controllers/Traits/Upload.php');
			$path5 = resource_path('views/admin/layout.blade.php');
			$path6 = resource_path('views/admin/limits.blade.php');
			$path7 = public_path('js/dropzone.min.css');
			$path8 = public_path('js/dropzone.min.js');
			$path9 = app_path('Http/Controllers/HomeController.php');
			$path10 = app_path('Http/Controllers/StripeController.php');
			$path11 = resource_path('views/default/contact.blade.php');
			$path12 = resource_path('views/emails/contact-email.blade.php');
			$path13 = resource_path('views/dashboard/add-funds.blade.php');
			$path14 = resource_path('views/includes/footer.blade.php');

			//============== Moving Files ================//
			$this->moveFile($file1, $path1, $copy);
			$this->moveFile($file2, $path2, $copy);
			$this->moveFile($file3, $path3, $copy);
			$this->moveFile($file4, $path4, $copy);
			$this->moveFile($file5, $path5, $copy);
			$this->moveFile($file6, $path6, $copy);
			$this->moveFile($file7, $path7, $copy);
			$this->moveFile($file8, $path8, $copy);
			$this->moveFile($file9, $path9, $copy);
			$this->moveFile($file10, $path10, $copy);
			$this->moveFile($file11, $path11, $copy);
			$this->moveFile($file12, $path12, $copy);
			$this->moveFile($file13, $path13, $copy);
			$this->moveFile($file14, $path14, $copy);


			//============ End Moving Files ===============//

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			return $upgradeDone;
		}
		//<<---- Version 3.4 ----->>

		if ($version == '3.6') {

			//============ Starting moving files...
			$path           = "v$version/";
			$pathAdmin      = "v$version/admin/";
			$oldVersion     = '3.5';
			$copy           = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files ================//
			$file1 = $path . 'Helper.php';
			$file2 = $path . 'AdminController.php';
			$file3 = $path . 'StripeController.php';
			$file4 = $path . 'Upload.php';
			$file5 = $path . 'payments-settings.blade.php';
			$file6 = $path . 'collections.blade.php';
			$file7 = $path . 'UserController.php';

			//============== Paths ================//
			$path1 = app_path('Helper.php');
			$path2 = app_path('Http/Controllers/AdminController.php');
			$path3 = app_path('Http/Controllers/StripeController.php');
			$path4 = app_path('Http/Controllers/Traits/Upload.php');
			$path5 = resource_path('views/admin/payments-settings.blade.php');
			$path6 = resource_path('views/includes/collections.blade.php');
			$path7 = app_path('Http/Controllers/UserController.php');

			//============== Moving Files ================//
			$this->moveFile($file1, $path1, $copy);
			$this->moveFile($file2, $path2, $copy);
			$this->moveFile($file3, $path3, $copy);
			$this->moveFile($file4, $path4, $copy);
			$this->moveFile($file5, $path5, $copy);
			$this->moveFile($file6, $path6, $copy);
			$this->moveFile($file7, $path7, $copy);

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 3.6 ----->>

		if ($version == '3.7') {

			//============ Starting moving files...
			$oldVersion = '3.6';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			if (!Schema::hasColumn(
				'admin_settings',
				'daily_limit_downloads',
				'fee_commission_non_exclusive',
				'who_can_sell',
				'show_counter',
				'show_categories_index',
				'free_photo_upload',
				'price_formats'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->unsignedInteger('daily_limit_downloads');
					$table->unsignedInteger('fee_commission_non_exclusive');
					$table->enum('who_can_sell', ['all', 'admin'])->default('all');
					$table->enum('who_can_upload', ['all', 'admin'])->default('all');
					$table->enum('show_counter', ['on', 'off'])->default('on');
					$table->enum('show_categories_index', ['on', 'off'])->default('on');
					$table->enum('free_photo_upload', ['on', 'off'])->default('on');
					$table->enum('price_formats', ['0', '1'])->default('1')->comment('0 Manual, 1 Automatic');
				});

				if (Schema::hasColumn('admin_settings', 'fee_commission_non_exclusive')) {
					AdminSettings::whereId(1)->update([
						'fee_commission_non_exclusive' => 70
					]);
				}
			}

			if (!Schema::hasColumn('users', 'author_exclusive')) {
				Schema::table('users', function ($table) {
					$table->enum('author_exclusive', ['yes', 'no'])->default('yes');
				});
			}

			if (!Schema::hasColumn('downloads', 'type', 'size')) {
				Schema::table('downloads', function ($table) {
					$table->string('type', 5);
					$table->string('size', 10);
				});
			}

			//============== Files ================//
			$file1 = 'Helper.php';
			$file2 = 'AdminController.php';
			$file3 = 'Upload.php';
			$file4 = 'PayPalController.php';
			$file5 = 'UserController.php';
			$file6 = 'ImagesController.php';
			$file7 = 'RegisterController.php';

			$file8 = 'home.blade.php';
			$file9 = 'notifications.blade.php';
			$file10 = 'withdrawal-processed.blade.php';
			$file11 = 'limits.blade.php';
			$file12 = 'upload.blade.php';
			$file13 = 'edit.blade.php';
			$file14 = 'show.blade.php';
			$file15 = 'navbar.blade.php';
			$file16 = 'account.blade.php';
			$file17 = 'nav-pills.blade.php';
			$file18 = 'settings.blade.php';
			$file19 = 'payments-settings.blade.php';
			$file20 = 'profile.blade.php';

			//============== Moving Files ================//
			$this->moveFile($path . $file1, $APP . $file1, $copy);
			$this->moveFile($path . $file2, $CONTROLLERS . $file2, $copy);
			$this->moveFile($path . $file3, $TRAITS . $file3, $copy);
			$this->moveFile($path . $file4, $CONTROLLERS . $file4, $copy);
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file6, $CONTROLLERS . $file6, $copy);
			$this->moveFile($path . $file7, $CONTROLLERS_AUTH . $file7, $copy);

			$this->moveFile($path . $file8, $VIEWS_INDEX . $file8, $copy);
			$this->moveFile($path . $file9, $VIEWS_USERS . $file9, $copy);
			$this->moveFile($path . $file10, $VIEWS_EMAILS . $file10, $copy);

			$this->moveFile($path . $file11, $VIEWS_ADMIN . $file11, $copy);
			$this->moveFile($path . $file12, $VIEWS_IMAGES . $file12, $copy);
			$this->moveFile($path . $file13, $VIEWS_IMAGES . $file13, $copy);
			$this->moveFile($path . $file14, $VIEWS_IMAGES . $file14, $copy);
			$this->moveFile($path . $file15, $VIEWS_INCLUDES . $file15, $copy);
			$this->moveFile($path . $file16, $VIEWS_USERS . $file16, $copy);
			$this->moveFile($path . $file17, $VIEWS_INCLUDES . $file17, $copy);
			$this->moveFile($path . $file18, $VIEWS_ADMIN . $file18, $copy);
			$this->moveFile($path . $file19, $VIEWS_ADMIN . $file19, $copy);
			$this->moveFile($path . $file20, $VIEWS_USERS . $file20, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 3.7 ----->>

		if ($version == '3.8') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}


			$replaceFavicon = 'url(\'public/img\', $settings->favicon)';

			$fileConfig = 'resources/views/admin/layout.blade.php';
			file_put_contents(
				$fileConfig,
				str_replace(
					'URL::asset(\'public/img/favicon.png\')',
					$replaceFavicon,
					file_get_contents($fileConfig)
				)
			);

			$fileConfig = 'resources/views/dashboard/layout.blade.php';
			file_put_contents(
				$fileConfig,
				str_replace(
					'URL::asset(\'public/img/favicon.png\')',
					$replaceFavicon,
					file_get_contents($fileConfig)
				)
			);

			if (!Schema::hasColumn(
				'admin_settings',
				'logo',
				'favicon',
				'image_header',
				'image_bottom',
				'watermark',
				'header_colors',
				'header_cameras',
				'avatar',
				'cover',
				'img_category',
				'img_collection',
				'youtube',
				'pinterest',
				'lightbox'
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->string('logo', 100);
					$table->string('favicon', 100);
					$table->string('image_header', 100);
					$table->string('image_bottom', 100);
					$table->string('watermark', 100);
					$table->string('header_colors', 100);
					$table->string('header_cameras', 100);
					$table->string('avatar', 100);
					$table->string('cover', 100);
					$table->string('img_category', 100);
					$table->string('img_collection', 100);
					$table->string('youtube', 200);
					$table->string('pinterest', 200);
					$table->enum('lightbox', ['on', 'off'])->default('on');
				});

				if (Schema::hasColumn('admin_settings', 'logo')) {
					AdminSettings::whereId(1)->update([
						'logo' => 'logo.png',
						'favicon' => 'favicon.png',
						'image_header' => 'header_index.jpg',
						'image_bottom' => 'cover.jpg',
						'watermark' => 'watermark.png',
						'header_colors' => 'header_colors.jpg',
						'header_cameras' => 'header_cameras.jpg',
						'avatar' => 'default.jpg',
						'cover' => 'cover.jpg',
						'img_category' => 'default.jpg',
						'img_collection' => 'img-collection.jpg'
					]);
				}
			}

			Schema::table('users', function ($table) {
				$table->index('avatar');
				$table->index('cover');
			});


			file_put_contents(
				'routes/web.php',
				"
Route::get('user/dashboard/downloads','DashboardController@downloads')->middleware('auth');
Route::get('files/preview/{size}/{path}', 'ImagesController@image')->where('path', '.*');
Route::get('assets/preview/{path}.{ext}', 'ImagesController@preview');",
				FILE_APPEND
			);

			$replace = '<!-- Links -->
			<li @if(Request::is(\'user/dashboard/downloads\')) class="active" @endif>
				<a href="{{ url(\'user/dashboard/downloads\') }}"><i class="fa fa-download"></i> <span>{{ trans(\'misc.downloads\') }}</span></a>
			</li><!-- ./Links -->

			</ul><!-- /.sidebar-menu -->';

			$fileConfig = 'resources/views/dashboard/layout.blade.php';
			file_put_contents(
				$fileConfig,
				str_replace(
					'</ul><!-- /.sidebar-menu -->',
					$replace,
					file_get_contents($fileConfig)
				)
			);


			//============== Files ================//
			$file1 = 'Query.php';
			$file2 = 'AdminController.php';
			$file3 = 'Upload.php';
			$file4 = 'HomeController.php';
			$file5 = 'UserController.php';
			$file6 = 'ImagesController.php';
			$file7 = 'RegisterController.php';
			$file21 = 'DashboardController.php';
			$file19 = 'userTraits.php';

			$file8 = 'tags.blade.php';
			$file9 = 'app.blade.php';
			$file10 = 'explore.blade.php';
			$file11 = '404.blade.php';
			$file12 = 'img-collection.jpg';
			$file13 = 'user_suspended.blade.php';
			$file14 = 'show.blade.php';
			$file15 = 'navbar.blade.php';
			$file16 = 'theme.blade.php';
			$file17 = 'nav-pills.blade.php';
			$file18 = 'settings.blade.php';
			$file20 = 'lazysizes.min.js';

			$file22 = 'footer.blade.php';
			$file23 = 'profiles-social.blade.php';
			$file24 = 'downloads.blade.php';
			$file25 = 'dashboard.blade.php';
			$file26 = 'dashboard.blade.php';
			$file27 = 'images.blade.php';
			$file28 = 'collections.blade.php';


			//============== Moving Files ================//
			$this->moveFile($path . $file1, $MODELS . $file1, $copy);
			$this->moveFile($path . $file2, $CONTROLLERS . $file2, $copy);
			$this->moveFile($path . $file3, $TRAITS . $file3, $copy);
			$this->moveFile($path . $file4, $CONTROLLERS . $file4, $copy);
			$this->moveFile($path . $file5, $CONTROLLERS . $file5, $copy);
			$this->moveFile($path . $file6, $CONTROLLERS . $file6, $copy);
			$this->moveFile($path . $file7, $CONTROLLERS_AUTH . $file7, $copy);
			$this->moveFile($path . $file8, $VIEWS_DEFAULT . $file8, $copy);
			$this->moveFile($path . $file9, $VIEWS . $file9, $copy);
			$this->moveFile($path . $file10, $VIEWS_INDEX . $file10, $copy);
			$this->moveFile($path . $file11, $VIEWS_ERRORS . $file11, $copy);
			$this->moveFile($path . $file12, $PUBLIC_IMG . $file12, $copy);
			$this->moveFile($path . $file13, $VIEWS_ERRORS . $file13, $copy);
			$this->moveFile($path . $file14, $VIEWS_IMAGES . $file14, $copy);
			$this->moveFile($path . $file15, $VIEWS_INCLUDES . $file15, $copy);
			$this->moveFile($path . $file16, $VIEWS_ADMIN . $file16, $copy);
			$this->moveFile($path . $file17, $VIEWS_INCLUDES . $file17, $copy);
			$this->moveFile($path . $file18, $VIEWS_ADMIN . $file18, $copy);
			$this->moveFile($path . $file19, $TRAITS . $file19, $copy);
			$this->moveFile($path . $file20, $PUBLIC_JS . $file20, $copy);
			$this->moveFile($path . $file21, $CONTROLLERS . $file21, $copy);
			$this->moveFile($path . $file22, $VIEWS_INCLUDES . $file22, $copy);
			$this->moveFile($path . $file23, $VIEWS_ADMIN . $file23, $copy);
			$this->moveFile($path . $file24, $VIEWS_DASHBOARD . $file24, $copy);
			$this->moveFile($pathAdmin . $file25, $VIEWS_ADMIN . $file25, $copy);
			$this->moveFile($path . $file26, $VIEWS_DASHBOARD . $file26, $copy);
			$this->moveFile($path . $file27, $VIEWS_INCLUDES . $file27, $copy);
			$this->moveFile($path . $file28, $VIEWS_INCLUDES . $file28, $copy);

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			// Delete folder
			if ($copy == false) {
				File::deleteDirectory("v$version");
			}

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 3.8 ----->>

		if ($version == '4.0') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Start Query ====================================

			// Insert on AdminSettings
			if (!Schema::hasColumn(
				'admin_settings',
				'google_login',
				'logo_light',
				'referral_system',
				'maintenance_mode',
				'company',
				'country',
				'address',
				'city',
				'zip',
				'vat',
				'phone',
				'percentage_referred',
				'referral_transaction_limit',
				'custom_css',
				'custom_js',
				'color_default',
				'img_section',
				'stripe_connect',
				'payout_method_paypal',
				'payout_method_bank',
			)) {
				Schema::table('admin_settings', function ($table) {
					$table->enum('google_login', ['on', 'off'])->default('off');
					$table->string('logo_light', 200)->default('logo-light.png');
					$table->enum('referral_system', ['on', 'off'])->default('off');
					$table->enum('maintenance_mode', ['on', 'off'])->default('off');
					$table->string('company', 200);
					$table->string('country', 200);
					$table->string('address', 200);
					$table->string('city', 200);
					$table->string('zip', 200);
					$table->string('vat', 200);
					$table->string('phone', 200);
					$table->unsignedInteger('percentage_referred')->default(5);
					$table->char('referral_transaction_limit', 10)->default('1');
					$table->text('custom_css');
					$table->text('custom_js');
					$table->string('color_default', 200)->default('#212529');
					$table->string('img_section', 100)->default('section.png');
					$table->unsignedTinyInteger('stripe_connect')->default(0);
					$table->unsignedTinyInteger('payout_method_paypal')->default(1);
					$table->unsignedTinyInteger('payout_method_bank')->default(1);
				});
			}

			if (!Schema::hasTable('plans')) {
				Schema::create('plans', function ($table) {
					$table->bigIncrements('id');
					$table->char('plan_id', 20)->index();
					$table->string('name', 100);
					$table->decimal('price', 10, 2);
					$table->decimal('price_year', 10, 2);
					$table->string('downloadable_content', 100);
					$table->unsignedInteger('downloads_per_month');
					$table->unsignedTinyInteger('unused_downloads_rollover')->default(0);
					$table->string('license', 100);
					$table->unsignedInteger('download_limits');
					$table->enum('status', ['0', '1'])->default(1);
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('tax_rates')) {
				Schema::create('tax_rates', function ($table) {
					$table->increments('id');
					$table->string('name', 250)->index('name');
					$table->boolean('type')->index('type')->default(1);
					$table->decimal('percentage', 5, 2);
					$table->string('country', 100)->nullable();
					$table->string('state', 100)->nullable();
					$table->char('iso_state', 10)->nullable();
					$table->string('stripe_id', 100)->nullable();
					$table->enum('status', ['0', '1'])->default(1);
					$table->timestamps();
				});
			}

			if (!Schema::hasColumn('deposits', 'status')) {
				Schema::table('deposits', function ($table) {
					$table->enum('status', ['active', 'pending'])->default('active');
				});
			}

			Schema::table('deposits', function ($table) {
				$table->string('txn_id', 200)->nullable()->change();
			});

			Schema::table('downloads', function ($table) {
				$table->string('type', 100)->change();
			});

			if (!Schema::hasColumn('payment_gateways', 'logo', 'webhook_secret', 'subscription')) {
				Schema::table('payment_gateways', function ($table) {
					$table->string('logo', 100);
					$table->string('webhook_secret', 255);
					$table->unsignedTinyInteger('subscription');
				});
			}

			if (Schema::hasColumn('payment_gateways', 'logo')) {

				PaymentGateways::whereName('PayPal')->update([
					'logo' => 'paypal.png',
				]);

				PaymentGateways::whereName('Stripe')
					->update(['logo' => 'stripe.png']);
			}

			if (!Schema::hasColumn(
				'purchases',
				'txn_id',
				'mode',
				'percentage_applied',
				'referred_commission',
				'payment_gateway',
				'taxes',
				'direct_payment'
			)) {
				Schema::table('purchases', function ($table) {
					$table->string('txn_id', 250)->after('id');
					$table->string('mode')->default('normal');
					$table->string('percentage_applied', 50);
					$table->unsignedInteger('referred_commission');
					$table->string('payment_gateway', 100)->nullable()->after('earning_net_admin');
					$table->text('taxes');
					$table->unsignedTinyInteger('direct_payment')->default(0);
				});
			}

			if (!Schema::hasTable('invoices')) {
				Schema::create('invoices', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('purchases_id')->index()->nullable();
					$table->unsignedInteger('subscriptions_id')->index()->nullable();
					$table->unsignedInteger('deposits_id')->index()->nullable();
					$table->decimal('amount', 10, 2);
					$table->string('percentage_applied', 50);
					$table->float('transaction_fee', 10, 2)->nullable();
					$table->text('taxes')->nullable();
					$table->string('status', 100)->default('paid');
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('referrals')) {
				Schema::create('referrals', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('referred_by')->index();
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('referral_transactions')) {
				Schema::create('referral_transactions', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('referrals_id')->index();
					$table->unsignedInteger('user_id')->index();
					$table->unsignedInteger('referred_by')->index();
					$table->float('earnings', 10, 2);
					$table->char('type', 25);
					$table->timestamps();
				});
			}

			Schema::table('notifications', function ($table) {
				$table->unsignedInteger('type')->change();
			});

			if (!Schema::hasTable('two_factor_codes')) {
				Schema::create('two_factor_codes', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('user_id');
					$table->string('code', 25);
					$table->timestamps();
				});
			}

			if (!Schema::hasColumn(
				'users',
				'two_factor_auth',
				'stripe_connect_id',
				'completed_stripe_onboarding',
				'stripe_id',
				'pm_type',
				'pm_last_four',
				'downloads',
				'trial_ends_at',
			)) {
				Schema::table('users', function ($table) {
					$table->enum('two_factor_auth', ['yes', 'no'])->default('no');
					$table->string('stripe_connect_id')->nullable();
					$table->boolean('completed_stripe_onboarding')->default(false);
					$table->string('stripe_id')->nullable()->index();
					$table->string('pm_type', 255)->nullable();
					$table->string('pm_last_four', 4)->nullable();
					$table->unsignedInteger('downloads');
					$table->timestamp('trial_ends_at')->nullable();
				});
			}

			Schema::table('users', function ($table) {
				$table->decimal('funds', 10, 2)->change();
			});

			if (!Schema::hasTable('states')) {
				Schema::create('states', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedInteger('countries_id')->index();
					$table->string('name', 250)->index('name');
					$table->char('code', 10)->index('code');
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('roles_and_permissions')) {
				Schema::create('roles_and_permissions', function ($table) {
					$table->bigIncrements('id');
					$table->string('name', 250)->index('name');
					$table->longText('permissions')->index();
					$table->enum('editable', ['0', '1'])->default('1');
					$table->timestamps();
				});
			}

			if (Schema::hasTable('roles_and_permissions')) {
				DB::table('roles_and_permissions')->insert([
					'name' => 'Super Admin',
					'permissions' => 'full_access',
					'editable' => '0',
					'created_at' => now()
				]);
			}

			if (!Schema::hasTable('stripe_state_tokens')) {
				Schema::create('stripe_state_tokens', function ($table) {
					$table->id();
					$table->foreignId('user_id');
					$table->string('token')->nullable();
					$table->timestamps();
				});
			}

			if (!Schema::hasTable('subscriptions')) {
				Schema::create('subscriptions', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedBigInteger('user_id');
					$table->string('name', 255);
					$table->string('paypal_id', 255);
					$table->string('stripe_id', 255)->unique();
					$table->string('stripe_status', 255);
					$table->string('stripe_price', 255)->nullable();
					$table->integer('quantity')->nullable();
					$table->timestamp('trial_ends_at')->nullable();
					$table->timestamp('ends_at')->nullable();
					$table->enum('cancelled', ['yes', 'no'])->default('no');
					$table->enum('rebill_wallet', ['on', 'off'])->default('off');
					$table->string('interval', 255)->default('month');
					$table->text('taxes')->nullable();
					$table->string('payment_gateway', 255);
					$table->timestamps();

					$table->index(['user_id', 'stripe_status']);
				});
			}

			if (!Schema::hasTable('subscription_items')) {
				Schema::create('subscription_items', function ($table) {
					$table->bigIncrements('id');
					$table->unsignedBigInteger('subscription_id');
					$table->string('stripe_id')->unique();
					$table->string('stripe_product');
					$table->string('stripe_price');
					$table->integer('quantity')->nullable();
					$table->timestamps();

					$table->unique(['subscription_id', 'stripe_price']);
				});
			}

			if (!Schema::hasTable('failed_jobs')) {
				Schema::create('failed_jobs', function ($table) {
					$table->id();
					$table->string('uuid')->unique();
					$table->text('connection');
					$table->text('queue');
					$table->longText('payload');
					$table->longText('exception');
					$table->timestamp('failed_at')->useCurrent();
				});
			}

			// Create Table Jobs
			if (!Schema::hasTable('jobs')) {
				Schema::create('jobs', function ($table) {
					$table->bigIncrements('id');
					$table->string('queue')->index();
					$table->longText('payload');
					$table->unsignedTinyInteger('attempts');
					$table->unsignedInteger('reserved_at')->nullable();
					$table->unsignedInteger('available_at');
					$table->unsignedInteger('created_at');
				});
			} // <<--- End Create Table Jobs

			if (!Schema::hasColumn('pages', 'lang')) {
				Schema::table('pages', function ($table) {
					$table->char('lang', 10)->default(session('locale'));
				});
			}

			//=============== End Query SQL ====================================

			// Update Version
			$this->settings->whereId(1)->update([
				'version' => $version
			]);

			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');

			return $upgradeDone;
		}
		//<<---- End Version 4.0 ----->>

		if ($version == '4.1') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'app.php' => $CONFIG,

				'UploadTrait.php' => $TRAITS,

				//============ CONTROLLERS =================//
				'ImagesController.php' => $CONTROLLERS,

				'styles.css' => $PUBLIC_CSS,

				'Addons.php' => $MODELS,
				'CollectionsImages.php' => $MODELS,

				'collections-grid.blade.php' => $VIEWS_INCLUDES,

				'home.blade.php' => $VIEWS_INDEX,
				'wizard.blade.php' => $VIEWS_INSTALLER,
				'show.blade.php' => $VIEWS_IMAGES,
				'dashboard.blade.php' => $VIEWS_DASHBOARD,
				'503.blade.php' => $VIEWS_ERRORS,

			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN,
				'purchases.blade.php' => $VIEWS_ADMIN,
				'images.blade.php' => $VIEWS_ADMIN,
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				if (!Schema::hasTable('addons')) {
					Schema::create('addons', function ($table) {
						$table->increments('id');
						$table->string('name', 200);
						$table->string('slug', 200);
						$table->string('icon', 200);
						$table->timestamps();
					});
				}
				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} // copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.1 ----->>

		if ($version == '4.2') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'web.php' => $ROUTES, //

				'Helper.php' => $APP, //

				'UploadTrait.php' => $TRAITS, //

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //
				'ImagesController.php' => $CONTROLLERS, //
				'StripeController.php' => $CONTROLLERS, //
				'StripeWebHookController.php' => $CONTROLLERS, //
				'PayPalController.php' => $CONTROLLERS, //
				'CheckoutController.php' => $CONTROLLERS, //
				'CollectionController.php' => $CONTROLLERS, //
				'HomeController.php' => $CONTROLLERS, //

				'SellOption.php' => $MIDDLEWARE, //

				'styles.css' => $PUBLIC_CSS, //
				'admin-styles.css' => $PUBLIC_CSS, //

				'add-funds.js' => $PUBLIC_JS, //
				'checkout.js' => $PUBLIC_JS, //
				'functions.js' => $PUBLIC_JS, //

				'Collections.php' => $MODELS, //

				'app.blade.php' => $VIEWS_LAYOUTS, //

				'show.blade.php' => $VIEWS_IMAGES, //
				'upload.blade.php' => $VIEWS_IMAGES, //
				'edit.blade.php' => $VIEWS_IMAGES, //

				'add-funds.blade.php' => $VIEWS_DASHBOARD, //

				'footer.blade.php' => $VIEWS_INCLUDES, //
				'images.blade.php' => $VIEWS_INCLUDES, //
				'navbar.blade.php' => $VIEWS_INCLUDES, //
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //
				'categories-listing.blade.php' => $VIEWS_INCLUDES, //
				'menu-dropdown.blade.php' => $VIEWS_INCLUDES, //

				'navbar-settings.blade.php' => $VIEWS_USERS, //
				'collection-detail.blade.php' => $VIEWS_USERS, //

				'Palette.php' => base_path('vendor' . $DS . 'league' . $DS . 'color-extractor' . $DS . 'src' . $DS . 'League' . $DS . 'ColorExtractor' . $DS . '')
			];

			$filesAdmin = [
				'layout.blade.php' => $VIEWS_ADMIN, //
				'limits.blade.php' => $VIEWS_ADMIN, //
				'collections.blade.php' => $VIEWS_ADMIN, //
				'settings.blade.php' => $VIEWS_ADMIN, //
				'permissions.blade.php' => $VIEWS_ADMIN, //
				'payments-settings.blade.php' => $VIEWS_ADMIN, //
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'default_price_photos', 'link_blog', 'tax_on_wallet')) {
					Schema::table('admin_settings', function ($table) {
						$table->unsignedInteger('default_price_photos');
						$table->string('link_blog', 255);
						$table->boolean('tax_on_wallet')->default(true);
					});
				}

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} // copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.2 ----->>

		if ($version == '4.3') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'app.php' => $CONFIG, //v4.3
				'flutterwave.php' => $CONFIG, //v4.3

				'SocialAccountService.php' => $APP, //v4.3

				'UploadTrait.php' => $TRAITS, //v4.3

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.3
				'StripeWebHookController.php' => $CONTROLLERS, //v4.3

				'styles.css' => $PUBLIC_CSS, //v4.3

				'app.blade.php' => $VIEWS_LAYOUTS, //v4.3

				'add-funds.blade.php' => $VIEWS_DASHBOARD, //v4.3

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.3
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.3
				'navbar.blade.php' => $VIEWS_INCLUDES, //v4.3
				'menu-dropdown.blade.php' => $VIEWS_INCLUDES, //v4.3
				'footer.blade.php' => $VIEWS_INCLUDES, //v4.3

				'explore.blade.php' => $VIEWS_INDEX, //v4.3
				'show.blade.php' => $VIEWS_IMAGES, //v4.3

			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, //v4.3
				'images.blade.php' => $VIEWS_ADMIN, //v4.3
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				file_put_contents(
					'.env',
					"\nFLW_PUBLIC_KEY=\nFLW_SECRET_KEY=\n",
					FILE_APPEND
				);

				if (!Schema::hasColumn('admin_settings', 'comments')) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('comments')->default(true);
					});
				}

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} // copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.3 ----->>

		if ($version == '4.4') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'web.php' => $ROUTES, //v4.4

				'UploadTrait.php' => $TRAITS, //v4.4

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.4
				'CommentsController.php' => $CONTROLLERS, //v4.4
				'DashboardController.php' => $CONTROLLERS, //v4.4
				'PagesController.php' => $CONTROLLERS, //v4.4
				'HomeController.php' => $CONTROLLERS, //v4.4
				'StripeController.php' => $CONTROLLERS, //v4.4
				'PayPalController.php' => $CONTROLLERS, //v4.4
				'ImagesController.php' => $CONTROLLERS, //v4.4
				'UserController.php' => $CONTROLLERS, //v4.4

				'Authenticate.php' => $MIDDLEWARE, //v4.4

				'styles.css' => $PUBLIC_CSS, //v4.4

				'add-funds.js' => $PUBLIC_JS, //v4.4
				'functions.js' => $PUBLIC_JS, //v4.4
				'install-app.js' => $PUBLIC_JS, //v4.4

				'ckeditor-init.js' => public_path('js' . $DS . 'ckeditor') . $DS, //4.4

				'app.blade.php' => $VIEWS_LAYOUTS, //4.4

				'category.blade.php' => $VIEWS_DEFAULT, //v4.4

				'login.blade.php' => $VIEWS_AUTH, //v4.4

				'withdrawals-configure.blade.php' => $VIEWS_DASHBOARD, //v4.4

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.4
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.4
				'categories-listing.blade.php' => $VIEWS_INCLUDES, //v4.4
				'navbar.blade.php' => $VIEWS_INCLUDES, //v4.4
				'menu-dropdown.blade.php' => $VIEWS_INCLUDES,
				'footer.blade.php' => $VIEWS_INCLUDES, //v4.4
				'comments.blade.php' => $VIEWS_INCLUDES, //v4.4

				'edit.blade.php' => $VIEWS_IMAGES, //v4.4
				'show.blade.php' => $VIEWS_IMAGES, //v4.4
				'upload.blade.php' => $VIEWS_IMAGES, //v4.4

				'account.blade.php' => $VIEWS_USERS, //4.4
				'profile.blade.php' => $VIEWS_USERS, //4.4

			];

			$filesAdmin = [
				'settings.blade.php' => $VIEWS_ADMIN, //4.4
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.4
				'permissions.blade.php' => $VIEWS_ADMIN, //v4.4
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				DB::statement("ALTER TABLE users CHANGE author_exclusive author_exclusive ENUM('yes','no') DEFAULT 'no' NOT NULL");

				if (!Schema::hasColumn('admin_settings', 'banner_cookies', 'stripe_connect_countries')) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('banner_cookies')->default(true);
						$table->longText('stripe_connect_countries');
					});
				}

				if (!Schema::hasColumn('images', 'data_iptc')) {
					Schema::table('images', function ($table) {
						$table->boolean('data_iptc')->default(false);
					});
				}

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} // copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.4 ----->>

		if ($version == '4.5') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'web.php' => $ROUTES, //v4.5

				'UploadTrait.php' => $TRAITS, //v4.5

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.5
				'AdminUserController.php' => $CONTROLLERS, //v4.5
				'DashboardController.php' => $CONTROLLERS, //v4.5
				'HomeController.php' => $CONTROLLERS, //v4.5

				'RebillWallet.php' => $JOBS, //v4.5

				'styles.css' => $PUBLIC_CSS, //v4.5

				'functions.js' => $PUBLIC_JS, //v4.5

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.5
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //v4.5
				'images.blade.php' => $VIEWS_INCLUDES, //v4.5

				'home.blade.php' => $VIEWS_INDEX, //v4.5

				'show.blade.php' => $VIEWS_IMAGES, //v4.5

			];

			$filesAdmin = [
				'announcements.blade.php' => $VIEWS_ADMIN, //v4.5
				'css-js.blade.php' => $VIEWS_ADMIN, //v4.5
				'dashboard.blade.php' => $VIEWS_ADMIN, //v4.5
				'images.blade.php' => $VIEWS_ADMIN, //v4.5
				'members.blade.php' => $VIEWS_ADMIN, //v4.5
				'deposits.blade.php' => $VIEWS_ADMIN, //v4.5
				'subscriptions.blade.php' => $VIEWS_ADMIN, //v4.5
				'collections.blade.php' => $VIEWS_ADMIN, //v4.5
				'purchases.blade.php' => $VIEWS_ADMIN, //v4.5
				'maintenance.blade.php' => $VIEWS_ADMIN, //v4.5
				'settings.blade.php' => $VIEWS_ADMIN, //4.5
				'permissions.blade.php' => $VIEWS_ADMIN, //v4.5
				'layout.blade.php' => $VIEWS_ADMIN, //v4.5
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn(
					'admin_settings',
					'announcement',
					'type_announcement',
					'announcement_cookie'
				)) {
					Schema::table('admin_settings', function ($table) {

						$table->longText('announcement')->collation('utf8mb4_unicode_ci');
						$table->char('type_announcement', 10);
						$table->string('announcement_cookie', 25);
					});
				}

				if (!Schema::hasColumn('images', 'date_time_original')) {
					Schema::table('images', function ($table) {
						$table->string('date_time_original', 50)->nullable();
					});
				}

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.5 ----->>

		if ($version == '4.6') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'web.php' => $ROUTES, //v4.6

				'UploadTrait.php' => $TRAITS,

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.6
				'ImagesController.php' => $CONTROLLERS, //v4.6
				'RolesAndPermissionsController.php' => $CONTROLLERS, //v4.6
				'PayPalController.php' => $CONTROLLERS, //v4.6
				'PlansController.php' => $CONTROLLERS, //v4.6

				'RebillWallet.php' => $JOBS,

				'User.php' => $MODELS, //v4.6

				'styles.css' => $PUBLIC_CSS, //v4.6

				'functions.js' => $PUBLIC_JS,

				'install-app.js' => $PUBLIC_JS, //v4.6

				'register.blade.php' => $VIEWS_AUTH, //v4.6

				'app.blade.php' => $VIEWS_LAYOUTS, //v4.6

				'pricing.blade.php' => $VIEWS_DEFAULT, //v4.6
				'sitemaps.blade.php' => $VIEWS_DEFAULT, //v4.6

				'404.blade.php' => $VIEWS_ERRORS, //v4.6
				'500.blade.php' => $VIEWS_ERRORS, //v4.6

				'css_general.blade.php' => $VIEWS_INCLUDES, //v4.6
				'javascript_general.blade.php' => $VIEWS_INCLUDES,
				'images.blade.php' => $VIEWS_INCLUDES,

				'home.blade.php' => $VIEWS_INDEX,

				'show.blade.php' => $VIEWS_IMAGES, //v4.6

			];

			$filesAdmin = [
				'edit-image.blade.php' => $VIEWS_ADMIN, //v4.6
				'edit-plan.blade.php' => $VIEWS_ADMIN, //v4.6
				'pwa.blade.php' => $VIEWS_ADMIN, //v4.6
				'dashboard.blade.php' => $VIEWS_ADMIN,
				'images.blade.php' => $VIEWS_ADMIN,
				'edit-member.blade.php' => $VIEWS_ADMIN, //v4.6
				'deposits.blade.php' => $VIEWS_ADMIN,
				'subscriptions.blade.php' => $VIEWS_ADMIN,
				'collections.blade.php' => $VIEWS_ADMIN,
				'purchases.blade.php' => $VIEWS_ADMIN,
				'maintenance.blade.php' => $VIEWS_ADMIN,
				'settings.blade.php' => $VIEWS_ADMIN, //v4.6
				'theme.blade.php' => $VIEWS_ADMIN, //v4.6
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.6
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				//============ Start Query SQL ====================================
				if (!Schema::hasColumn('admin_settings', 'status_pwa', 'popular_plan_color')) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('status_pwa')->default(true);
						$table->string('popular_plan_color', 20)->default('#ff3300');
					});
				} // Schema

				if (!Schema::hasColumn('plans', 'popular')) {
					Schema::table('plans', function ($table) {
						$table->boolean('popular')->default(false)->after('download_limits');
					});
				} // Schema

				//=============== End Query SQL ====================================

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.6 ----->>

		if ($version == '4.7') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [

				'Helper.php' => $APP, //v4.7

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.7
				'LoginController.php' => $CONTROLLERS_AUTH, //v4.7
				'PayPalController.php' => $CONTROLLERS, //v4.7

				'jqueryTimeago_es.js' => public_path('js' . $DS . 'timeago') . $DS, //v4.7

				'categories-listing.blade.php' => $VIEWS_INCLUDES, //v4.7

			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.7 ----->>

		if ($version == '4.8') {

			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'SearchTrait.php' => $TRAITS, //v4.8
				'FunctionsTrait.php' => $TRAITS, //v4.8

				'Query.php' => $MODELS, //v4.8

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //v4.8
				'SubscriptionsController.php' => $CONTROLLERS_AUTH, //v4.8
				'HomeController.php' => $CONTROLLERS, //v4.8

				'home.blade.php' => $VIEWS_INDEX, //v4.8

				'show.blade.php' => $VIEWS_IMAGES, //v4.8
				'search.blade.php' => $VIEWS_DEFAULT, //v4.8

				'contact-email.blade.php' => $VIEWS_EMAILS, //v4.8

				'account.blade.php' => $VIEWS_USERS, //v4.8
			];

			$filesAdmin = [
				'announcements.blade.php' => $VIEWS_ADMIN, //v4.8
				'edit-image.blade.php' => $VIEWS_ADMIN, //v4.8
				'limits.blade.php' => $VIEWS_ADMIN, //v4.8
				'payments-settings.blade.php' => $VIEWS_ADMIN, //v4.8
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {

				if (!Schema::hasColumn('admin_settings', 'announcement_show', 'extended_license_price')) {
					Schema::table('admin_settings', function ($table) {
						$table->unsignedInteger('extended_license_price')->default(10);
						$table->string('announcement_show', 20)->default('all');
					});
				} // Schema

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.8 ----->>

		if ($version == '4.9') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'api.php' => $ROUTES, //Affected
				'web.php' => $ROUTES, //Affected

				'Helper.php' => $APP, //Affected

				'app.php' => $CONFIG, //Affected
				'captcha.php' => $CONFIG, //Affected
				'path.php' => $CONFIG, //Affected

				'admin-styles.css' => $PUBLIC_CSS, //Affected
				'styles.css' => $PUBLIC_CSS, //Affected
				'tagin.css' => public_path('js' . $DS . 'tagin') . $DS, //Affected
				'bootstrap.min.css' => $PUBLIC_CSS, //Affected
				'bootstrap.min.js' => $PUBLIC_JS, //Affected
				'bootstrap.bundle.min.js.map' => $PUBLIC_JS, //Affected
				'bootstrap-icons.css' => $PUBLIC_CSS, //Affected
				'bootstrap-icons.woff' => public_path('webfonts' . $DS . 'bootstrap') . $DS, //Affected
				'bootstrap-icons.woff2' => public_path('webfonts' . $DS . 'bootstrap') . $DS, //Affected
				'add-funds.js' => $PUBLIC_JS, //Affected
				'core.min.js' => $PUBLIC_JS, //Affected
				'admin-functions.js' => $PUBLIC_JS, //Affected
				'switch-theme.js' => $PUBLIC_JS, //Affected
				'functions.js' => $PUBLIC_JS, //Affected
				'OneSignalSDKWorker.js' => $PUBLIC_JS, //Affected

				'bank.png' => public_path('img' . $DS . 'payments') . $DS,

				'UploadTrait.php' => $TRAITS, //Affected
				'FunctionsTrait.php' => $TRAITS, //Affected
				'SearchTrait.php' => $TRAITS, //Affected
				'PushNotificationTrait.php' => $TRAITS, //Affected

				'AdminDepositPending.php' => $NOTIFICATIONS, //Affected
				'NewSale.php' => $NOTIFICATIONS, //Affected
				'DepositVerification.php' => $NOTIFICATIONS, //Affected

				'Query.php' => $MODELS, //Affected
				'Deposits.php' => $MODELS, //Affected
				'Images.php' => $MODELS, //Affected
				'Invoices.php' => $MODELS, //Affected
				'Comments.php' => $MODELS, //Affected
				'CollectionsImages.php' => $MODELS, //Affected
				'Collections.php' => $MODELS, //Affected
				'Categories.php' => $MODELS, //Affected
				'Subcategories.php' => $MODELS, //Affected
				'Purchases.php' => $MODELS, //Affected
				'UserDevices.php' => $MODELS, //Affected
				'Notifications.php' => $MODELS, //Affected
				'User.php' => $MODELS, //Affected

				//============ CONTROLLERS =================//
				'AddFundsController.php' => $CONTROLLERS, //Affected
				'AdminController.php' => $CONTROLLERS, //Affected
				'CheckoutController.php' => $CONTROLLERS, //Affected
				'DashboardController.php' => $CONTROLLERS, //Affected
				'LangController.php' => $CONTROLLERS, //Affected
				'HomeController.php' => $CONTROLLERS, //Affected
				'ImagesController.php' => $CONTROLLERS, //Affected
				'PayPalController.php' => $CONTROLLERS, //Affected
				'PushNotificationsController.php' => $CONTROLLERS, //Affected
				'UserController.php' => $CONTROLLERS, //Affected

				'AdminSettingsMiddleware.php' => $MIDDLEWARE, //Affected
				'EncryptCookies.php' => $MIDDLEWARE, //Affected
				'Language.php' => $MIDDLEWARE, //Affected

				'Kernel.php' => app_path('Http') . $DS, //Affected

				'ViewServiceProvider.php' => $PROVIDERS, //Affected

				//============== Views ===================//
				'app.blade.php' => $VIEWS_LAYOUTS, //Affected

				'login.blade.php' => $VIEWS_AUTH, //Affected
				'register.blade.php' => $VIEWS_AUTH, //Affected

				'add-funds.blade.php' => $VIEWS_DASHBOARD, //Affected
				'photos.blade.php' => $VIEWS_DASHBOARD, //Affected
				'purchases.blade.php' => $VIEWS_DASHBOARD, //Affected
				'sales.blade.php' => $VIEWS_DASHBOARD, //Affected
				'downloads.blade.php' => $VIEWS_DASHBOARD, //Affected

				'404.blade.php' => $VIEWS_ERRORS, //Affected

				'home.blade.php' => $VIEWS_INDEX, //Affected
				'explore.blade.php' => $VIEWS_INDEX, //Affected

				'show.blade.php' => $VIEWS_IMAGES, //Affected
				'edit.blade.php' => $VIEWS_IMAGES, //Affected
				'upload.blade.php' => $VIEWS_IMAGES, //Affected

				'comments.blade.php' => $VIEWS_INCLUDES, //Affected
				'css_general.blade.php' => $VIEWS_INCLUDES, //Affected
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //Affected
				'collections-grid.blade.php' => $VIEWS_INCLUDES, //Affected
				'footer.blade.php' => $VIEWS_INCLUDES, //Affected
				'navbar.blade.php' => $VIEWS_INCLUDES, //Affected
				'images.blade.php' => $VIEWS_INCLUDES, //Affected
				'users.blade.php' =>  $VIEWS_INCLUDES, //Affected
				'menu-dropdown.blade.php' =>  $VIEWS_INCLUDES, //Affected

				'pricing.blade.php' => $VIEWS_DEFAULT, //Affected
				'collections.blade.php' => $VIEWS_DEFAULT, //Affected
				'category.blade.php' => $VIEWS_DEFAULT, //Affected
				'subcategory.blade.php' => $VIEWS_DEFAULT, //Affected
				'contact.blade.php' => $VIEWS_DEFAULT, //Affected
				'tags.blade.php' => $VIEWS_DEFAULT, //Affected

				'collection-detail.blade.php' => $VIEWS_USERS, //Affected
				'profile.blade.php' => $VIEWS_USERS, //Affected
				'navbar-settings.blade.php' => $VIEWS_USERS, //Affected
			];

			$filesAdmin = [
				'bank-settings.blade.php' => $VIEWS_ADMIN, //Affected
				'google.blade.php' => $VIEWS_ADMIN, //Affected
				'maintenance.blade.php' => $VIEWS_ADMIN, //Affected
				'theme.blade.php' => $VIEWS_ADMIN, //Affected
				'purchases.blade.php' => $VIEWS_ADMIN, //Affected
				'pwa.blade.php' => $VIEWS_ADMIN, //Affected
				'layout.blade.php' => $VIEWS_ADMIN, //Affected
				'add-subcategories.blade.php' => $VIEWS_ADMIN, //Affected
				'subcategories.blade.php' => $VIEWS_ADMIN, //Affected
				'edit-subcategories.blade.php' => $VIEWS_ADMIN, //Affected
				'edit-image.blade.php' => $VIEWS_ADMIN, //Affected
				'paypal-settings.blade.php' => $VIEWS_ADMIN, //Affected
				'permissions.blade.php' => $VIEWS_ADMIN, //Affected
				'push_notifications.blade.php' => $VIEWS_ADMIN, //Affected
				'deposits.blade.php' => $VIEWS_ADMIN, //Affected
				'deposits-view.blade.php' => $VIEWS_ADMIN, //Affected
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//=============== Start QUERY SQL
				file_put_contents(
					'.env',
					"\n\nNOCAPTCHA_SITEKEY=" . env('INVISIBLE_RECAPTCHA_SITEKEY') . "\nNOCAPTCHA_SECRET=" . env('INVISIBLE_RECAPTCHA_SECRETKEY') . "\n\nPAYPAL_WEBHOOK_ID=",
					FILE_APPEND
				);

				Schema::table('payment_gateways', function ($table) {
					$table->decimal('fee_cents', 6, 2)->change();
				});

				if (!Schema::hasColumn(
					'admin_settings',
					'push_notification_status',
					'onesignal_appid',
					'onesignal_restapi'
				)) {
					Schema::table('admin_settings', function ($table) {
						$table->boolean('push_notification_status')->default(0);
						$table->string('onesignal_appid', 150);
						$table->string('onesignal_restapi', 150);
					});
				}

				if (!Schema::hasTable('user_devices')) {
					Schema::create('user_devices', function ($table) {
						$table->id();
						$table->unsignedBigInteger('user_id');
						$table->string('player_id')->unique();
						$table->char('device_type', 5)->nullable();
						$table->timestamps();
					});
				}

				if (!DB::table('reserved')->where('name', 'lang')->first()) {
					DB::table('reserved')->insert([
						['name' => 'lang']
					]);
				}

				if (!Schema::hasColumn('images', 'subcategories_id')) {
					Schema::table('images', function ($table) {
						$table->unsignedInteger('subcategories_id')->index('subcategories_id');
					});
				}

				if (!Schema::hasTable('subcategories')) {
					Schema::create('subcategories', function ($table) {
						$table->increments('id');
						$table->unsignedInteger('category_id')->index('category_id');
						$table->string('name', 255);
						$table->string('slug', 255)->index('slug');
						$table->enum('mode', ['on', 'off'])->default('on');
					});
				}

				PaymentGateways::whereName('PayPal')->update([
					'subscription' => true
				]);

				if (!Schema::hasColumn('payment_gateways', 'bank_info')) {
					Schema::table('payment_gateways', function ($table) {
						$table->text('bank_info');
					});
				}

				if (!Schema::hasColumn('deposits', 'screenshot_transfer')) {
					Schema::table('deposits', function ($table) {
						$table->string('screenshot_transfer', 100)->nullable();
					});
				}

				if (Schema::hasTable('payment_gateways')) {
					\DB::table('payment_gateways')->insert(
						[
							[
								'name' => 'Bank',
								'type' => 'bank',
								'enabled' => '0',
								'fee' => 0.0,
								'fee_cents' => 0.00,
								'email' => '',
								'key' => '',
								'key_secret' => '',
								'logo' => 'bank.png',
								'subscription' => false,
								'bank_info' => '',
								'token' => str_random(150),
							]
						]
					);
				}

				//================ End QUERY SQL

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 4.9 ----->>

		if ($version == '5.0') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'Helper.php' => $APP,

				//============ CONTROLLERS =================//
				'AdminController.php' => $CONTROLLERS, //5.0
				'ImagesController.php' => $CONTROLLERS, //5.0
				'HomeController.php' => $CONTROLLERS, //5.0

				'UploadTrait.php' => $TRAITS, //5.0

				'admin-styles.css' => $PUBLIC_CSS, //5.0
				'styles.css' => $PUBLIC_CSS, //5.0

				'functions.js' => $PUBLIC_JS, //5.0

				//============ VIEWS =================//
				'app.blade.php' => $VIEWS_LAYOUTS, //5.0

				'login.blade.php' => $VIEWS_AUTH, //5.0
				'register.blade.php' => $VIEWS_AUTH, //5.0
				'reset.blade.php' => $VIEWS_AUTH_PASS, //5.0
				'email.blade.php' => $VIEWS_AUTH_PASS, //5.0

				'503.blade.php' => $VIEWS_ERRORS, //5.0

				'footer.blade.php' => $VIEWS_INCLUDES, //5.0
				'collections-grid.blade.php' => $VIEWS_INCLUDES, //5.0
				'images.blade.php' => $VIEWS_INCLUDES, //5.0
				'menu-dropdown.blade.php' =>  $VIEWS_INCLUDES, //5.0
				'navbar.blade.php' => $VIEWS_INCLUDES, //5.0

				'show.blade.php' => $VIEWS_IMAGES, //5.0

				'category.blade.php' => $VIEWS_DEFAULT, //5.0
				'subcategory.blade.php' => $VIEWS_DEFAULT, //5.0
				'tags.blade.php' => $VIEWS_DEFAULT, //5.0
			];

			$filesAdmin = [
				'add-categories.blade.php' => $VIEWS_ADMIN, //5.0
				'add-subcategories.blade.php' => $VIEWS_ADMIN, //5.0
				'categories.blade.php' => $VIEWS_ADMIN, //5.0
				'edit-categories.blade.php' => $VIEWS_ADMIN, //5.0
				'edit-subcategories.blade.php' => $VIEWS_ADMIN, //5.0
				'settings.blade.php' => $VIEWS_ADMIN, //5.0
				'layout.blade.php' => $VIEWS_ADMIN, //5.0
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				//=============== SQL QUERIES ==============//
				if (!Schema::hasColumn('admin_settings', 'theme')) {
					Schema::table('admin_settings', function ($table) {
						$table->string('theme', 100)->default('light');
					});
				}

				if (!Schema::hasColumn('categories', 'description')) {
					Schema::table('categories', function ($table) {
						$table->text('description')->nullable();
						$table->string('keywords', 255)->nullable();
					});
				}

				if (!Schema::hasColumn('subcategories', 'description')) {
					Schema::table('subcategories', function ($table) {
						$table->text('description')->nullable();
						$table->string('keywords', 255)->nullable();
					});
				}

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.0 ----->>

		if ($version == '5.1') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'serviceworker.js' => $ROOT, //5.1
				'composer.json' => $ROOT, //5.1
				'composer.lock' => $ROOT, //5.1

				'styles.css' => $PUBLIC_CSS, //5.1

				'bootstrap.min.css' => $PUBLIC_CSS, //5.1
				'bootstrap.min.js' => $PUBLIC_JS, //5.1
				'bootstrap.bundle.min.js.map' => $PUBLIC_JS, //5.1
				'bootstrap-icons.css' => $PUBLIC_CSS, //5.1
				'bootstrap-icons.woff' => $PUBLIC_FONTS_BOOTSTRAP, //5.1
				'bootstrap-icons.woff2' => $PUBLIC_FONTS_BOOTSTRAP, //5.1

				'register.blade.php' => $VIEWS_AUTH, //5.1

				'sitemaps.blade.php' => $VIEWS_DEFAULT, //5.1

				'footer.blade.php' => $VIEWS_INCLUDES, //5.1
				'css_general.blade.php' => $VIEWS_INCLUDES, //5.1
				'javascript_general.blade.php' => $VIEWS_INCLUDES, //5.1

				'show.blade.php' => $VIEWS_IMAGES, //5.1

				'profile.blade.php' => $VIEWS_USERS, //5.1
			];

			$filesAdmin = [
				'withdrawals.blade.php' => $VIEWS_ADMIN, //5.1
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.1 ----->>

		if ($version == '5.2') {
			//============ Starting moving files...
			$oldVersion = '5.1';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'web.php' => $ROUTES, //5.2

				'composer.json' => $ROOT, //5.2
				'composer.lock' => $ROOT, //5.2

				'FunctionsTrait.php' => $TRAITS, //5.2

				'Helper.php' => $APP, //5.2

				'currencies.php' => $CONFIG, //5.2

				'Kernel.php' => app_path('Console') . $DS, //5.2

				'AdminController.php' => $CONTROLLERS, //5.2
				'ImagesController.php' => $CONTROLLERS, //5.2
				'StripeController.php' => $CONTROLLERS, //5.2
				'StripeWebHookController.php' => $CONTROLLERS, //5.2
				'PayPalController.php' => $CONTROLLERS, //5.2

				'core.min.js' => $PUBLIC_JS, //5.2

				'404.blade.php' => $VIEWS_ERRORS, //5.2
				'503.blade.php' => $VIEWS_ERRORS, //5.2

				'app.blade.php' => $VIEWS_LAYOUTS, //5.2

				'images.blade.php' => $VIEWS_INCLUDES, //5.2
				'pagination-links.blade.php' => $VIEWS_INCLUDES, //5.2

				'edit.blade.php' => $VIEWS_IMAGES, //5.2
				'show.blade.php' => $VIEWS_IMAGES, //5.2

				'search.blade.php' => $VIEWS_DEFAULT, //5.2
				'sitemaps.blade.php' => $VIEWS_DEFAULT, //5.2
				'sitemaps-media.blade.php' => $VIEWS_DEFAULT, //5.2
			];

			$filesAdmin = [
				'subscriptions.blade.php' => $VIEWS_ADMIN, //5.2
				'edit-image.blade.php' => $VIEWS_ADMIN, //5.2
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.2 ----->>

		if ($version == '5.3') {
			//============ Starting moving files...
			$oldVersion = '5.2';
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.3
				'composer.lock' => $ROOT, //5.3

				'bootstrap.min.css' => $PUBLIC_CSS, //5.3
				'bootstrap.min.css.map' => $PUBLIC_CSS, //5.3
				'bootstrap.min.js' => $PUBLIC_JS, //5.3
				'bootstrap.bundle.min.js.map' => $PUBLIC_JS, //5.3

				'Helper.php' => $APP, //5.3

				'FunctionsTrait.php' => $TRAITS, //5.3

				'SubscriptionsController.php' => $CONTROLLERS, //5.3

				'ViewServiceProvider.php' => $PROVIDERS, //5.3

				'login.blade.php' => $VIEWS_AUTH, //5.3
				'register.blade.php' => $VIEWS_AUTH, //5.3
				'reset.blade.php' => $VIEWS_AUTH_PASS, //5.3
				'email.blade.php' => $VIEWS_AUTH_PASS, //5.3

				'pricing.blade.php' => $VIEWS_DEFAULT, //5.3
				'tags.blade.php' => $VIEWS_DEFAULT, //5.3
				'tags-show.blade.php' => $VIEWS_DEFAULT, //5.3
				'subcategory.blade.php' => $VIEWS_DEFAULT, //5.3
				'members.blade.php' => $VIEWS_DEFAULT, //5.3
				'contact.blade.php' => $VIEWS_DEFAULT, //5.3
				'colors.blade.php' => $VIEWS_DEFAULT, //5.3
				'category.blade.php' => $VIEWS_DEFAULT, //5.3
				'categories.blade.php' => $VIEWS_DEFAULT, //5.3
				'cameras.blade.php' => $VIEWS_DEFAULT, //5.3
				'sitemaps-media.blade.php' => $VIEWS_DEFAULT, //5.3

				'show.blade.php' => $VIEWS_IMAGES, //5.3

				'explore.blade.php' => $VIEWS_INDEX, //v5.3

				'app.blade.php' => $VIEWS_LAYOUTS, //5.3

			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.3 ----->>

		if ($version == '5.4') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.4
				'composer.lock' => $ROOT, //5.4

				'RebillWallet.php' => $JOBS, //5.4

				'styles.css' => $PUBLIC_CSS, //5.4

				'InstallScriptController.php' => $CONTROLLERS, //5.4

				'SocialAccountService.php' => $APP, //5.4

				'ViewServiceProvider.php' => $PROVIDERS, //5.4

				'login.blade.php' => $VIEWS_AUTH, //5.4
				'register.blade.php' => $VIEWS_AUTH, //5.4

				'referrals.blade.php' => $VIEWS_USERS //5.4,

			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.4 ----->>

		if ($version == '5.5') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = true;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.json' => $ROOT, //5.5
				'composer.lock' => $ROOT, //5.5

				'app.php' => $CONFIG, //5.5
				'image.php' => $CONFIG, //5.5
				'debugbar.php' => $CONFIG, //5.5

				'Images.php' => $MODELS, //5.5

				'UploadTrait.php' => $TRAITS, //5.5

				'AdminController.php' => $CONTROLLERS, //5.5
				'DashboardController.php' => $CONTROLLERS, //5.5
				'ImagesController.php' => $CONTROLLERS, //5.5
				'HomeController.php' => $CONTROLLERS, //5.5
				'UserController.php' => $CONTROLLERS, //5.5

				'withdrawals.blade.php' => $VIEWS_DASHBOARD, //5.5

				'show.blade.php' => $VIEWS_IMAGES, //5.5

				'css_general.blade.php' => $VIEWS_INCLUDES, //5.5

				'admin-styles.css' => $PUBLIC_CSS, //5.5
			];

			$filesAdmin = [
				'subcategories.blade.php' => $VIEWS_ADMIN, //5.5
				'pwa.blade.php' => $VIEWS_ADMIN, //5.5
				'edit-page.blade.php' => $VIEWS_ADMIN, //5.5
				'maintenance.blade.php' => $VIEWS_ADMIN, //5.5
				'images.blade.php' => $VIEWS_ADMIN, //5.5
				'images_reported.blade.php' => $VIEWS_ADMIN, //5.5
				'deposits-view.blade.php' => $VIEWS_ADMIN, //5.5
				'countries.blade.php' => $VIEWS_ADMIN, //5.5
				'states.blade.php' => $VIEWS_ADMIN, //5.5
				'collections.blade.php' => $VIEWS_ADMIN, //5.5
				'languages.blade.php' => $VIEWS_ADMIN, //5.5
				'members.blade.php' => $VIEWS_ADMIN, //5.5
				'roles-permissions.blade.php' => $VIEWS_ADMIN, //5.5
				'members_reported.blade.php' => $VIEWS_ADMIN, //5.5
				'pages.blade.php' => $VIEWS_ADMIN, //5.5
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			//====== Move Files Admin =============
			foreach ($filesAdmin as $file => $root) {
				$this->moveFile($pathAdmin . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				Schema::table('subscriptions', function ($table) {
					$table->renameColumn('name', 'type');
				});

				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.5 ----->>

		if ($version == '5.6') {
			//============ Starting moving files...
			$oldVersion = $this->settings->version;
			$path       = "v$version/";
			$pathAdmin  = "v$version/admin/";
			$copy       = false;

			if ($this->settings->version == $version) {
				return redirect('/');
			}

			if ($this->settings->version != $oldVersion || !$this->settings->version) {
				return "<h2 style='text-align:center; margin-top: 30px; font-family: Arial, san-serif;color: #ff0000;'>Error! you must update from version $oldVersion</h2>";
			}

			//============== Files Affected ================//
			$files = [
				'composer.lock' => $ROOT, //5.6

				'UploadTrait.php' => $TRAITS, //5.6

				'User.php' => $MODELS, //5.6
				'Images.php' => $MODELS, //5.6
				'Query.php' => $MODELS, //5.6

				'ImagesController.php' => $CONTROLLERS, //5.6

				'tags.blade.php' => $VIEWS_DEFAULT, //5.6
				'sitemaps.blade.php' => $VIEWS_DEFAULT, //5.6
			];

			//======= Move Files =============
			foreach ($files as $file => $root) {
				$this->moveFile($path . $file, $root . $file, $copy);
			}

			// Copy UpgradeController
			if ($copy == true) {
				$this->moveFile($path . 'UpgradeController.php', $CONTROLLERS . 'UpgradeController.php', $copy);
			}

			if ($copy == false) {
				// Delete folder
				File::deleteDirectory("v$version");
			} //========= copy == false

			// Update Version
			$this->settings->update([
				'version' => $version
			]);

			// Clear Cache, Config and Views
			\Artisan::call('cache:clear');
			\Artisan::call('config:clear');
			\Artisan::call('view:clear');
			\Artisan::call('queue:restart');

			return $upgradeDone;
		} //<<---- End Version 5.6 ----->>

	} // <<--- method update
}
