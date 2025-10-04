<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ !is_null(request()->cookie('theme')) ? request()->cookie('theme') : $settings->theme }}" id="theme-asset">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('description_custom'){{ __('seo.description') }}">
    <meta name="keywords" content="@yield('keywords_custom'){{ __('seo.keywords') }}" />
    <meta name="theme-color" content="{{ $settings->color_default }}">
    <link rel="shortcut icon" href="{{ url('public/img', $settings->favicon) }}" />

    <title>{{ Helper::getNotify() }}@section('title')@show{{Helper::titleSite()}}</title>

    @include('includes.css_general')

    @if ($settings->status_pwa)
      @laravelPWA
    @endif
    
    @yield('css')

    @if ($settings->google_analytics != '')
      {!! $settings->google_analytics !!}
    @endif
  </head>
  <body>
    <div class="overlay" data-bs-toggle="offcanvas" data-bs-target="#offcanvas"></div>
  <div class="popout font-default"></div>

  <div class="wrap-loader">
  <div class="progress-wrapper display-none position-absolute w-100" id="progress">
    <div class="progress progress-container">
      <div class="progress-bar progress-bg" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
    </div>
    <div class="wrap-container">
      <div class="progress-info">
        <div class="progress-percentage">
          <span class="percent">0%</span>
        </div>
      </div>
    </div>

  </div>
  </div>

  @if ($settings->banner_cookies)
  <div class="fixed-bottom">
    <div class="d-flex justify-content-center align-items-center">
      <div class="text-center display-none bg-white showBanner shadow-sm mb-3 mx-2 border">
        {{trans('misc.cookies_text')}}

        <button class="btn btn-sm btn-custom ms-1" id="close-banner">
          {{trans('misc.go_it')}}
        </button>
      </div>
    </div>
  </div>
@endif


    <main>
      @if (! request()->is('login')
          && ! request()->is('register')
          && ! request()->is('password/*')
          )
      @include('includes.navbar')
    @endif

        @yield('content')

    @if (! request()->is('login')
        && ! request()->is('register')
        && ! request()->is('password/*')
        )
      @include('includes.footer')
    @endif

    </main>

    @include('includes.javascript_general')

    @yield('javascript')

     <div id="bodyContainer"></div>
     </body>
</html>
