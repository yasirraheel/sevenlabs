<?php

namespace App\Http\Middleware;

use Closure;
use Session;
use App\Models\Languages;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            if (Session::has('locale')) {
                app()->setLocale(session('locale'));
            } else {
                Session::put('locale', config('app.locale'));
    
                $availableLangs = Languages::all()->pluck('abbreviation');
                $userLangs = explode(',', $request->server('HTTP_ACCEPT_LANGUAGE'));
    
                foreach ($availableLangs as $lang) {
                    if (strpos($userLangs[0], '' . $lang . '') !== FALSE) {
                        app()->setLocale($lang);
                        Session::put('locale', $lang);
                        break;
                    }
                }
            }

        } catch (\Exception $e) {
        }

        return $next($request);
    }
}
