<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\UpgradeController;
use App\Http\Controllers\AddFundsController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\TaxRatesController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InstallScriptController;
use App\Http\Controllers\StripeConnectController;
use App\Http\Controllers\StripeWebHookController;
use App\Http\Controllers\SubscriptionsController;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\CountriesStatesController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\RolesAndPermissionsController;
use App\Http\Controllers\AjaxController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [HomeController::class, 'explore'])->name('home');
Route::get('home', function() {
    return redirect('/');
});

// Authentication
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::get('logout', [LoginController::class, 'logout']);

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('register', [RegisterController::class, 'register']);

Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset']);

// Social Login
Route::group(['middleware' => 'guest'], function() {
    Route::get('oauth/{provider}', [SocialAuthController::class, 'redirect'])->where('provider', '(facebook|google|twitter)$');
    Route::get('oauth/{provider}/callback', [SocialAuthController::class, 'callback'])->where('provider', '(facebook|google|twitter)$');
});

// Public Routes
Route::get('members',[HomeController::class, 'members']);
Route::get('categories',[HomeController::class, 'categories']);
Route::get('pricing',[HomeController::class, 'pricing']);
Route::get('category/{slug}',[HomeController::class, 'category']);
Route::get('category/{slug}/{subcategory}', [HomeController::class, 'subcategory']);
Route::get('tags',[HomeController::class, 'tags']);
Route::get('tags/{tags}',[HomeController::class, 'tagsShow']);
Route::get('search', [HomeController::class, 'getSearch']);
Route::get('contact',[HomeController::class, 'contact']);
Route::post('contact',[HomeController::class, 'contactStore']);

// TTS Routes
// TTS tasks route removed - app converted

// TTS Callback (no auth required - called by external API)
// TTS routes removed - app converted

// Account Verification
Route::get('verify/account/{confirmation_code}', [HomeController::class, 'getVerifyAccount'])->where('confirmation_code','[A-Za-z0-9]+');

// Static Pages
Route::get('page/{page}',[PagesController::class, 'show'])->where('page','[^/]*' );

// Sitemaps
Route::get('sitemaps.xml', function() {
    return response()->view('default.sitemaps')->header('Content-Type', 'application/xml');
});

// Authenticated User Routes
Route::group(['middleware' => 'auth'], function() {
    // Account Settings
    Route::get('account',[UserController::class, 'account']);
    Route::post('account',[UserController::class, 'update_account']);


    // Delete Account
    Route::get('account/delete',[UserController::class, 'delete']);
    Route::post('account/delete',[UserController::class, 'delete_account']);

    // Upload Avatar & Cover
    Route::post('upload/avatar',[UserController::class, 'upload_avatar']);
    Route::post('upload/cover',[UserController::class, 'upload_cover']);

    // User Features
    Route::get('likes',[UserController::class, 'userLikes']);
    Route::get('feed',[UserController::class, 'followingFeed']);
    Route::get('notifications',[UserController::class, 'notifications']);
    Route::get('notifications/delete',[UserController::class, 'notificationsDelete']);
    
    // Manual Notifications
    Route::get('manual-notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('manual-notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    // Report User
    Route::post('report/user',[UserController::class, 'report']);

    // Comments
    Route::post('comment/store',[CommentsController::class, 'store']);
    Route::post('comment/delete',[CommentsController::class, 'destroy']);
    Route::post('comment/like',[CommentsController::class, 'like']);

    // User Balance API
    Route::get('api/user/balance', [App\Http\Controllers\Api\UserController::class, 'getUserBalance']);

    // TTS API Routes removed - app converted

    // AJAX Routes
    Route::get('ajax/notifications', [AjaxController::class, 'notifications']);

    // Dashboard
    Route::get('user/dashboard',[DashboardController::class, 'dashboard']);
    Route::get('user/dashboard/deposits',[DashboardController::class, 'deposits']);
    Route::get('user/dashboard/add/funds',[DashboardController::class, 'addFunds']);
    Route::post('user/dashboard/add/funds',[AddFundsController::class, 'send']);

    // Withdrawals

    // Stripe Connect
    Route::get('stripe/connect', [StripeConnectController::class, 'redirectToStripe'])->name('redirect.stripe');
    Route::get('connect/{token}', [StripeConnectController::class, 'saveStripeAccount'])->name('save.stripe');

    // Subscriptions
    Route::post('buy/subscription',[SubscriptionsController::class, 'buy']);
    Route::get('account/subscription',[UserController::class, 'subscription']);
    Route::get('buy/subscription/success',[SubscriptionsController::class, 'success'])->name('success.subscription');
    Route::post('account/subscription/cancel',[SubscriptionsController::class, 'cancel']);

    // Recharge/Deposit
    Route::get('account/recharge',[UserController::class, 'recharge']);
    Route::post('account/recharge',[UserController::class, 'storeRecharge']);
});

// Comments Likes (Public)
Route::post('comments/likes',[CommentsController::class, 'getLikes']);

// User Profiles
Route::get('{slug}', [UserController::class, 'profile'])->where('slug','[A-Za-z0-9\_-]+')->name('profile');
Route::get('{slug}/followers', [UserController::class, 'followers'])->where('slug','[A-Za-z0-9\_-]+');
Route::get('{slug}/following', [UserController::class, 'following'])->where('slug','[A-Za-z0-9\_-]+');

// Admin Routes
Route::group(['middleware' => 'role'], function() {
    // Upgrades
    Route::get('update/{version}',[UpgradeController::class, 'update']);

    // Dashboard
    Route::get('panel/admin',[AdminController::class, 'dashboard'])->name('dashboard');

    // Categories Management
    Route::get('panel/admin/categories',[AdminController::class, 'categories'])->name('categories');
    Route::get('panel/admin/categories/add',[AdminController::class, 'addCategories']);
    Route::post('panel/admin/categories/add',[AdminController::class, 'storeCategories']);
    Route::get('panel/admin/categories/edit/{id}',[AdminController::class, 'editCategories']);
    Route::post('panel/admin/categories/update',[AdminController::class, 'updateCategories']);
    Route::post('panel/admin/categories/delete/{id}',[AdminController::class, 'deleteCategories']);

    // Subcategories
    Route::get('panel/admin/subcategories',[AdminController::class, 'subcategories']);
    Route::get('panel/admin/subcategories/add',[AdminController::class, 'addSubcategories']);
    Route::post('panel/admin/subcategories/add',[AdminController::class, 'storeSubcategories']);
    Route::get('panel/admin/subcategories/edit/{id}',[AdminController::class, 'editSubcategories']);
    Route::post('panel/admin/subcategories/update',[AdminController::class, 'updateSubcategories']);
    Route::post('panel/admin/subcategories/delete/{id}',[AdminController::class, 'deleteSubcategories']);

    // Settings
    Route::get('panel/admin/settings',[AdminController::class, 'settings'])->name('general_settings');
    Route::post('panel/admin/settings',[AdminController::class, 'saveSettings']);
    Route::get('panel/admin/settings/limits',[AdminController::class, 'settingsLimits']);
    Route::post('panel/admin/settings/limits',[AdminController::class, 'saveSettingsLimits']);

    Route::view('panel/admin/announcements','admin.announcements')->name('announcements');
    Route::post('panel/admin/announcements', [AdminController::class, 'storeAnnouncements']);

    // Members Management
    Route::get('panel/admin/members',[AdminUserController::class, 'index'])->name('members');
    Route::get('panel/admin/members/edit/{id}',[AdminUserController::class, 'edit']);
    Route::post('panel/admin/members/edit/{id}', [AdminUserController::class, 'update']);
    Route::post('panel/admin/members/{id}', [AdminUserController::class, 'destroy'])->name('user.destroy');

    // Reported Members
    Route::get('panel/admin/members-reported',[AdminController::class, 'members_reported'])->name('members_reported');
    Route::post('panel/admin/members-reported',[AdminController::class, 'delete_members_reported']);

    // Pages Management
    Route::get('panel/admin/pages',[PagesController::class, 'index'])->name('pages');
    Route::get('panel/admin/pages/create',[PagesController::class, 'create']);
    Route::post('panel/admin/pages/create',[PagesController::class, 'store']);
    Route::get('panel/admin/pages/edit/{id}',[PagesController::class, 'edit']);
    Route::post('panel/admin/pages/edit/{id}', [PagesController::class, 'update']);
    Route::post('panel/admin/pages/{id}', [PagesController::class, 'destroy'])->name('pages.destroy');

    // Social Profiles
    Route::get('panel/admin/profiles-social',[AdminController::class, 'profiles_social'])->name('profiles_social');
    Route::post('panel/admin/profiles-social',[AdminController::class, 'update_profiles_social']);

    // Google Settings
    Route::get('panel/admin/google',[AdminController::class, 'google'])->name('google');
    Route::post('panel/admin/google',[AdminController::class, 'update_google']);

    // Languages
    Route::get('panel/admin/languages',[LangController::class, 'index'])->name('languages');
    Route::get('panel/admin/languages/create',[LangController::class, 'create']);
    Route::post('panel/admin/languages/create',[LangController::class, 'store']);
    Route::get('panel/admin/languages/edit/{id}',[LangController::class, 'edit']);
    Route::post('panel/admin/languages/edit/{id}', [LangController::class, 'update']);
    Route::post('panel/admin/languages/{id}', [LangController::class, 'destroy'])->name('languages.destroy');

    // Theme
    Route::get('panel/admin/theme',[AdminController::class, 'theme'])->name('theme');
    Route::post('panel/admin/theme',[AdminController::class, 'themeStore']);

    // Custom CSS/JS
    Route::view('panel/admin/custom-css-js','admin.css-js')->name('custom_css_js');
    Route::post('panel/admin/custom-css-js',[AdminController::class, 'customCssJs']);

    // Payment Settings
    Route::get('panel/admin/payments',[AdminController::class, 'payments'])->name('payment_settings');
    Route::post('panel/admin/payments',[AdminController::class, 'savePayments']);
    Route::get('panel/admin/payments/{id}',[AdminController::class, 'paymentsGateways']);
    Route::post('panel/admin/payments/{id}',[AdminController::class, 'savePaymentsGateways']);

    // Deposits Management
    Route::get('panel/admin/deposits',[AdminController::class, 'deposits'])->name('deposits');
    Route::post('panel/admin/deposits/approve',[AdminController::class, 'approveDeposit'])->name('deposits.approve');
    Route::post('panel/admin/deposits/reject',[AdminController::class, 'rejectDeposit'])->name('deposits.reject');

    // Withdrawals Management

    // Maintenance
    Route::view('panel/admin/maintenance', 'admin.maintenance')->name('maintenance_mode');
    Route::post('panel/admin/maintenance',[AdminController::class, 'maintenance']);
    Route::get('panel/admin/clear-cache', [AdminController::class, 'clearCache']);

    // Billing - Payment Methods
    Route::get('panel/admin/billing',[AdminController::class, 'billing'])->name('billing');
    Route::post('panel/admin/payment-methods/store',[AdminController::class, 'storePaymentMethod'])->name('payment-methods.store');
    Route::get('panel/admin/payment-methods/{id}',[AdminController::class, 'getPaymentMethod'])->name('payment-methods.get');
    Route::post('panel/admin/payment-methods/update',[AdminController::class, 'updatePaymentMethod'])->name('payment-methods.update');
    Route::delete('panel/admin/payment-methods/{id}',[AdminController::class, 'deletePaymentMethod'])->name('payment-methods.delete');

    // Tax Rates
    Route::get('panel/admin/tax-rates',[TaxRatesController::class, 'show'])->name('tax_rates');
    Route::view('panel/admin/tax-rates/add', 'admin.add-tax');
    Route::post('panel/admin/tax-rates/add', [TaxRatesController::class, 'store']);
    Route::get('panel/admin/tax-rates/edit/{id}', [TaxRatesController::class, 'edit']);
    Route::post('panel/admin/tax-rates/update', [TaxRatesController::class, 'update']);
    Route::post('panel/admin/ajax/states', [TaxRatesController::class, 'getStates']);

    // Plans
    Route::get('panel/admin/plans',[PlansController::class, 'show'])->name('plans');
    Route::view('panel/admin/plans/add', 'admin.add-plan');
    Route::post('panel/admin/plans/add', [PlansController::class, 'store']);
    Route::get('panel/admin/plans/edit/{id}', [PlansController::class, 'edit']);
    Route::post('panel/admin/plans/update', [PlansController::class, 'update']);

    // Subscriptions
    Route::get('panel/admin/subscriptions',[AdminController::class, 'subscriptions'])->name('subscriptions');

    // Countries & States
    Route::get('panel/admin/countries', [CountriesStatesController::class, 'countries'])->name('countries');
    Route::view('panel/admin/countries/add', 'admin.add-country');
    Route::post('panel/admin/countries/add', [CountriesStatesController::class, 'addCountry']);
    Route::get('panel/admin/countries/edit/{id}', [CountriesStatesController::class, 'editCountry']);
    Route::post('panel/admin/countries/update', [CountriesStatesController::class, 'updateCountry']);
    Route::post('panel/admin/countries/delete/{id}', [CountriesStatesController::class, 'deleteCountry']);

    Route::get('panel/admin/states', [CountriesStatesController::class, 'states'])->name('states');
    Route::view('panel/admin/states/add', 'admin.add-state');
    Route::post('panel/admin/states/add', [CountriesStatesController::class, 'addState']);
    Route::get('panel/admin/states/edit/{id}', [CountriesStatesController::class, 'editState']);
    Route::post('panel/admin/states/update', [CountriesStatesController::class, 'updateState']);
    Route::post('panel/admin/states/delete/{id}', [CountriesStatesController::class, 'deleteState']);

    // Email Settings
    Route::view('panel/admin/settings/email','admin.email-settings')->name('email_settings');
    Route::post('panel/admin/settings/email',[AdminController::class, 'emailSettings']);

    // Storage
    Route::view('panel/admin/storage','admin.storage')->name('storage');
    Route::post('panel/admin/storage',[AdminController::class, 'storage']);

    // Social Login
    Route::view('panel/admin/social-login','admin.social-login')->name('social_login');
    Route::post('panel/admin/social-login',[AdminController::class, 'updateSocialLogin']);

    // PWA
    Route::view('panel/admin/pwa','admin.pwa')->name('pwa');
    Route::post('panel/admin/pwa',[AdminController::class, 'pwa']);

    // Roles & Permissions
    Route::get('panel/admin/roles-and-permissions', [RolesAndPermissionsController::class, 'index'])->name('role_and_permissions');
    Route::view('panel/admin/roles-and-permissions/create', 'admin.add-role');
    Route::post('panel/admin/roles-and-permissions/create', [RolesAndPermissionsController::class, 'store']);
    Route::get('panel/admin/roles-and-permissions/edit/{id}', [RolesAndPermissionsController::class, 'edit']);
    Route::post('panel/admin/roles-and-permissions/update', [RolesAndPermissionsController::class, 'update']);
    Route::post('panel/admin/roles-and-permissions/delete/{id}', [RolesAndPermissionsController::class, 'destroy']);

    // Manual Notifications
    Route::resource('panel/admin/manual-notifications', Admin\ManualNotificationController::class)->names([
        'index' => 'admin.manual_notifications.index',
        'create' => 'admin.manual_notifications.create',
        'store' => 'admin.manual_notifications.store',
        'show' => 'admin.manual_notifications.show',
        'edit' => 'admin.manual_notifications.edit',
        'update' => 'admin.manual_notifications.update',
        'destroy' => 'admin.manual_notifications.destroy',
    ]);
    Route::patch('panel/admin/manual-notifications/{manualNotification}/toggle-status', [Admin\ManualNotificationController::class, 'toggleStatus'])->name('admin.manual_notifications.toggle_status');
});

// Language Switching
Route::get('change/lang/{id}',[LangController::class, 'language'])->where(['id' => '[a-z]+']);

// Installation & Addons
Route::get('install/{addon}',[InstallController::class, 'install']);

// PayPal Routes
Route::get('payment/paypal',[PayPalController::class, 'show'])->name('paypal');
Route::get('paypal/success',[PayPalController::class, 'success'])->name('paypal.success');
Route::get('paypal/buy',[PayPalController::class, 'buy'])->name('paypal.buy');
Route::get('paypal/buy/success', [PayPalController::class, 'successBuy'])->name('buy.success');
Route::get('paypal/verify', [PayPalController::class, 'verifyTransaction'])->name('paypal.verify');
Route::get('payment/paypal/subscription', [PayPalController::class, 'subscription'])->name('paypal.subscription');
Route::post('webhook/paypal', [PayPalController::class, 'webhook'])->name('paypal.webhook');
Route::get('paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');

// Stripe Routes
Route::get('payment/stripe', [StripeController::class, 'show'])->name('stripe');
Route::post('payment/stripe/charge', [StripeController::class, 'charge']);
Route::get('payment/stripe/buy', [StripeController::class, 'buy'])->name('stripe.buy');
Route::get('payment/stripe/subscription', [StripeController::class, 'subscription'])->name('stripe.subscription');
Route::post('stripe/webhook', [StripeWebHookController::class, 'handleWebhook']);

// Miscellaneous Routes
Route::get('invoice/{id}',[UserController::class, 'invoice']);
Route::post('verify/2fa', [TwoFactorAuthController::class, 'verify']);
Route::post('2fa/resend', [TwoFactorAuthController::class, 'resend']);

// Installation Script
Route::get('installer/script',[InstallScriptController::class, 'wizard']);
Route::post('installer/script/database',[InstallScriptController::class, 'database']);
Route::post('installer/script/user',[InstallScriptController::class, 'user']);
