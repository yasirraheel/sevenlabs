<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ !is_null(request()->cookie('theme')) ? request()->cookie('theme') : $settings->theme }}" id="theme-asset">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ url('public/img', $settings->favicon) }}" />

    <title>{{ __('admin.admin') }}</title>

    <link href="{{ asset('public/css/core.min.css') }}?v={{$settings->version}}" rel="stylesheet">
    <link href="{{ asset('public/css/bootstrap.min.css') }}?v={{$settings->version}}" rel="stylesheet">
    <link href="{{ asset('public/css/bootstrap-icons.css') }}?v={{$settings->version}}" rel="stylesheet">
    <link href="{{ asset('public/css/admin-styles.css') }}?v={{$settings->version}}" rel="stylesheet">
    <link href="{{ asset('public/css/styles.css') }}?v={{$settings->version}}" rel="stylesheet">

    <script type="text/javascript">
        var URL_BASE = "{{ url('/') }}";
        var error = "{{trans('misc.error')}}";
        var delete_confirm = "{{trans('misc.delete_confirm')}}";
        var yes_confirm = "{{trans('misc.yes_confirm')}}";
        var yes = "{{trans('misc.yes')}}";
        var cancel_confirm = "{{trans('misc.cancel_confirm')}}";
        var timezone = "{{env('TIMEZONE')}}";
        var darkMode = "{{ __('misc.dark_mode') }}";
        var lightMode = "{{ __('misc.light_mode') }}";
     </script>

    <style>
     :root {
       --color-default: {{ $settings->color_default }};
    }
     </style>

    @yield('css')
  </head>
  <body>
  <div class="overlay" data-bs-toggle="offcanvas" data-bs-target="#sidebar-nav"></div>
  <div class="popout font-default"></div>

    <main>

      <div class="offcanvas offcanvas-start sidebar bg-dark text-white" tabindex="-1" id="sidebar-nav" data-bs-keyboard="false" data-bs-backdrop="false">
      <div class="offcanvas-header">
          <h5 class="offcanvas-title"><img src="{{ url('public/img', $settings->logo_light) }}" width="100" /></h5>
          <button type="button" class="btn-close btn-close-custom text-white toggle-menu d-lg-none" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
      </div>
      <div class="offcanvas-body px-0 scrollbar">
          <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-start list-sidebar" id="menu">

              @if (auth()->user()->hasPermission('dashboard'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin') }}" class="nav-link text-truncate @if (request()->is('panel/admin')) active @endif">
                      <i class="bi-speedometer2 me-2"></i> {{ __('admin.dashboard') }}
                  </a>
              </li><!-- /end list -->
            @endif

              @if (auth()->user()->hasPermission('general_settings'))
              <li class="nav-item">
                  <a href="#settings" data-bs-toggle="collapse" class="nav-link text-truncate dropdown-toggle @if (request()->is('panel/admin/settings') ||request()->is('panel/admin/settings/limits')) active @endif" @if (request()->is('panel/admin/settings') ||request()->is('panel/admin/settings/limits')) aria-expanded="true" @endif>
                      <i class="bi-gear me-2"></i> {{ __('admin.general_settings') }}
                  </a>
              </li><!-- /end list -->
            @endif

              <div class="collapse w-100 @if (request()->is('panel/admin/settings') || request()->is('panel/admin/settings/limits')) show @endif ps-3" id="settings">
                <li>
                <a class="nav-link text-truncate w-100 @if (request()->is('panel/admin/settings')) text-white @endif" href="{{ url('panel/admin/settings') }}">
                  <i class="bi-chevron-right fs-7 me-1"></i> {{ trans('admin.general') }}
                  </a>
                </li>
                <li>
                <a class="nav-link text-truncate @if (request()->is('panel/admin/settings/limits')) text-white @endif" href="{{ url('panel/admin/settings/limits') }}">
                  <i class="bi-chevron-right fs-7 me-1"></i> {{ trans('admin.limits') }}
                  </a>
                </li>
              </div><!-- /end collapse settings -->

              @if (auth()->user()->hasPermission('announcements'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/announcements') }}" class="nav-link text-truncate @if (request()->is('panel/admin/announcements')) active @endif">
                      <i class="bi-megaphone me-2"></i> {{ __('admin.announcements') }}
                  </a>
              </li><!-- /end list -->
            @endif

              @if (auth()->user()->hasPermission('maintenance_mode'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/maintenance') }}" class="nav-link text-truncate @if (request()->is('panel/admin/maintenance')) active @endif">
                      <i class="bi bi-tools me-2"></i> {{ __('admin.maintenance_mode') }}
                  </a>
              </li><!-- /end list -->
            @endif

            @if (auth()->user()->hasPermission('billing_information'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/billing') }}" class="nav-link text-truncate @if (request()->is('panel/admin/billing')) active @endif">
                      <i class="bi-receipt-cutoff me-2"></i> {{ __('admin.billing_information') }}
                  </a>
              </li><!-- /end list -->
            @endif

              <!-- Purchases menu removed - stock photo functionality -->

            <!-- Images permission check removed - stock photo functionality -->
              <!-- Images menu removed - stock photo functionality -->

            @if (auth()->user()->hasPermission('deposits'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/deposits') }}" class="nav-link text-truncate @if (request()->is('panel/admin/deposits')) active @endif">
                      <i class="bi-cash-stack me-2"></i>

                      @if ($depositsPendingCount <> 0)
                        <span class="badge rounded-pill bg-warning text-dark me-1">{{ $depositsPendingCount }}</span>
                      @endif

                      {{ __('admin.deposits') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('withdrawals'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/withdrawals') }}" class="nav-link text-truncate @if (request()->is('panel/admin/withdrawals')) active @endif">
                      <i class="bi-bank me-2"></i>

                      @if ($withdrawalsPendingCount <> 0)
                        <span class="badge rounded-pill bg-warning text-dark me-1">{{ $withdrawalsPendingCount }}</span>
                      @endif

                      {{ __('admin.withdrawals') }}
                  </a>
              </li><!-- /end list -->
              @endif

            @if (auth()->user()->hasPermission('push_notifications'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/push-notifications') }}" class="nav-link text-truncate @if (request()->is('panel/admin/push-notifications')) active @endif">
                      <i class="bi-app-indicator me-2"></i> {{ __('admin.push_notifications') }}
                  </a>
              </li><!-- /end list -->
            @endif

                @if (auth()->user()->hasPermission('tax_rates'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/tax-rates') }}" class="nav-link text-truncate @if (request()->is('panel/admin/tax-rates')) active @endif">
                      <i class="bi-receipt me-2"></i> {{ __('admin.tax_rates') }}
                  </a>
              </li><!-- /end list -->
            @endif

            @if (auth()->user()->hasPermission('plans'))
            <li class="nav-item">
                <a href="{{ url('panel/admin/plans') }}" class="nav-link text-truncate @if (request()->is('panel/admin/plans')) active @endif">
                    <i class="bi-box2 me-2"></i> {{ __('admin.plans') }}
                </a>
            </li><!-- /end list -->
            @endif

            @if (auth()->user()->hasPermission('subscriptions'))
            <li class="nav-item">
                <a href="{{ url('panel/admin/subscriptions') }}" class="nav-link text-truncate @if (request()->is('panel/admin/subscriptions')) active @endif">
                    <i class="bi-arrow-repeat me-2"></i> {{ __('admin.subscriptions') }}
                </a>
            </li><!-- /end list -->
            @endif

            @if (auth()->user()->hasPermission('countries'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/countries') }}" class="nav-link text-truncate @if (request()->is('panel/admin/countries')) active @endif">
                      <i class="bi-globe me-2"></i> {{ __('admin.countries') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('states'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/states') }}" class="nav-link text-truncate @if (request()->is('panel/admin/states')) active @endif">
                      <i class="bi-pin-map me-2"></i> {{ __('admin.states') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('email_settings'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/settings/email') }}" class="nav-link text-truncate @if (request()->is('panel/admin/settings/email')) active @endif">
                      <i class="bi-at me-2"></i> {{ __('admin.email_settings') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('storage'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/storage') }}" class="nav-link text-truncate @if (request()->is('panel/admin/storage')) active @endif">
                      <i class="bi-server me-2"></i> {{ __('admin.storage') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @foreach (Addons::all() as $addon)
                @if (auth()->user()->hasPermission($addon->name))
                  <li class="nav-item">
                      <a href="{{ url('panel/admin', $addon->slug) }}" class="nav-link text-truncate @if (request()->is('panel/admin/'.$addon->slug.'')) active @endif">
                          <i class="{{ $addon->icon }} me-2"></i> {{ __('admin.'.$addon->name) }}
                      </a>
                  </li><!-- /end list -->
                @endif
              @endforeach

              @if (auth()->user()->hasPermission('theme'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/theme') }}" class="nav-link text-truncate @if (request()->is('panel/admin/theme')) active @endif">
                      <i class="bi-brush me-2"></i> {{ __('admin.theme') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('custom_css_js'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/custom-css-js') }}" class="nav-link text-truncate @if (request()->is('panel/admin/custom-css-js')) active @endif">
                      <i class="bi-code-slash me-2"></i> {{ __('admin.custom_css_js') }}
                  </a>
              </li><!-- /end list -->
              @endif

              <!-- Collections menu removed - stock photo functionality -->

              @if (auth()->user()->hasPermission('languages'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/languages') }}" class="nav-link text-truncate @if (request()->is('panel/admin/languages')) active @endif">
                      <i class="bi-translate me-2"></i> {{ __('admin.languages') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('categories'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/categories') }}" class="nav-link text-truncate @if (request()->is('panel/admin/categories')) active @endif">
                      <i class="bi-list-stars me-2"></i> {{ __('admin.categories') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('categories'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/subcategories') }}" class="nav-link text-truncate @if (request()->is('panel/admin/subcategories')) active @endif">
                      <i class="bi-list-stars me-2"></i> {{ __('admin.subcategories') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('members'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/members') }}" class="nav-link text-truncate @if (request()->is('panel/admin/members')) active @endif">
                      <i class="bi-people me-2"></i> {{ __('admin.members') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('role_and_permissions'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/roles-and-permissions') }}" class="nav-link text-truncate @if (request()->is('panel/admin/roles-and-permissions')) active @endif">
                      <i class="bi-person-badge me-2"></i> {{ __('admin.role_and_permissions') }}
                  </a>
              </li><!-- /end list -->
            @endif

            @if (auth()->user()->hasPermission('members_reported'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/members-reported') }}" class="nav-link text-truncate @if (request()->is('panel/admin/members-reported')) active @endif">
                      <i class="bi-person-x me-2"></i>

                      @if ($usersReported <> 0)
                      <span class="badge rounded-pill bg-warning text-dark me-1">{{ $usersReported }}</span>
                      @endif

                      {{ __('admin.members_reported') }}
                  </a>
              </li><!-- /end list -->
                @endif

              <!-- Images reported menu removed - stock photo functionality -->

              @if (auth()->user()->hasPermission('pages'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/pages') }}" class="nav-link text-truncate @if (request()->is('panel/admin/pages')) active @endif">
                      <i class="bi-file-earmark-text me-2"></i> {{ __('admin.pages') }}
                  </a>
              </li><!-- /end list -->
                @endif

                @if (auth()->user()->hasPermission('payment_settings'))
              <li class="nav-item">
                  <a href="#payments" data-bs-toggle="collapse" class="nav-link text-truncate dropdown-toggle @if (request()->is('panel/admin/payments') || request()->is('panel/admin/payments/*')) active @endif" @if (request()->is('panel/admin/payments') || request()->is('panel/admin/payments/*')) aria-expanded="true" @endif>
                      <i class="bi-credit-card me-2"></i> {{ __('admin.payment_settings') }}
                  </a>
              </li><!-- /end list -->

              <div class="collapse w-100 ps-3 @if (request()->is('panel/admin/payments') || request()->is('panel/admin/payments/*')) show @endif" id="payments">
                <li>
                <a class="nav-link text-truncate @if (request()->is('panel/admin/payments')) text-white @endif" href="{{ url('panel/admin/payments') }}">
                  <i class="bi-chevron-right fs-7 me-1"></i> {{ trans('admin.general') }}
                  </a>
                </li>

                @foreach (PaymentGateways::all() as $key)
                <li>
                <a class="nav-link text-truncate @if (request()->is('panel/admin/payments/'.$key->id.'')) text-white @endif" href="{{ url('panel/admin/payments', $key->id) }}">
                  <i class="bi-chevron-right fs-7 me-1"></i> {{ $key->type == 'bank' ? __('misc.bank_transfer') : $key->name }}
                  </a>
                </li>
              @endforeach
              </div><!-- /end collapse settings -->
              @endif

              @if (auth()->user()->hasPermission('profiles_social'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/profiles-social') }}" class="nav-link text-truncate @if (request()->is('panel/admin/profiles-social')) active @endif">
                      <i class="bi-share me-2"></i> {{ __('admin.profiles_social') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('social_login'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/social-login') }}" class="nav-link text-truncate @if (request()->is('panel/admin/social-login')) active @endif">
                      <i class="bi-facebook me-2"></i> {{ __('admin.social_login') }}
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('google'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/google') }}" class="nav-link text-truncate @if (request()->is('panel/admin/google')) active @endif">
                      <i class="bi-google me-2"></i> Google
                  </a>
              </li><!-- /end list -->
              @endif

              @if (auth()->user()->hasPermission('pwa'))
              <li class="nav-item">
                  <a href="{{ url('panel/admin/pwa') }}" class="nav-link text-truncate @if (request()->is('panel/admin/pwa')) active @endif">
                      <i class="bi-phone me-2"></i> PWA
                  </a>
              </li><!-- /end list -->
              @endif

          </ul>
      </div>
  </div>

  <header class="py-3 mb-3 shadow-custom">

    <div class="container-fluid d-grid gap-3 px-4 justify-content-end position-relative">

      <div class="d-flex align-items-center">

        <a class="text-dark ms-2 animate-up-2 me-4" href="{{ url('/') }}">
        {{ trans('admin.view_site') }} <i class="bi-arrow-up-right"></i>
        </a>

        <div class="flex-shrink-0 dropdown">
          <a href="#" class="d-block link-dark text-decoration-none" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
           <img src="{{ Storage::url(config('path.avatar').auth()->user()->avatar) }}" width="32" height="32" class="rounded-circle">
          </a>
          <ul class="dropdown-menu dropdown-menu-macos arrow-dm" aria-labelledby="dropdownUser2">
            @include('includes.menu-dropdown')
          </ul>
        </div>

        <a class="ms-4 toggle-menu d-block d-lg-none text-dark fs-3 position-absolute start-0" data-bs-toggle="offcanvas" data-bs-target="#sidebar-nav" href="#">
            <i class="bi-list"></i>
            </a>
      </div>
    </div>
  </header>

  <div class="container-fluid">
      <div class="row">
          <div class="col min-vh-100 admin-container p-4">
              @yield('content')
          </div>
      </div>
  </div>

  <footer class="admin-footer px-4 py-3 shadow-custom">
    &copy; {{ $settings->title }} v{{$settings->version}} - {{ date('Y') }}
  </footer>

</main>

    <!-- Placed at the end of the document so the pages load faster -->
    <script src="{{ asset('public/js/core.min.js') }}?v={{$settings->version}}"></script>
    <script src="{{ asset('public/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('public/js/ckeditor/ckeditor.js')}}"></script>
    <script src="{{ asset('public/js/select2/select2.full.min.js') }}"></script>
    <script src="{{ asset('public/js/admin-functions.js') }}?v={{$settings->version}}"></script>
    <script src="{{ asset('public/js/switch-theme.js') }}?v={{$settings->version}}"></script>

    @yield('javascript')

    @auth
    <script>
    // Load SevenLabs user data when dropdown is opened (Admin Panel)
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggle = document.getElementById('dropdownUser2');
        const balanceElement = document.getElementById('balance-amount');
        const creditsElement = document.getElementById('credits-amount');
        
        console.log('Admin Panel - Dropdown elements found:', {
            dropdownToggle: !!dropdownToggle,
            balanceElement: !!balanceElement,
            creditsElement: !!creditsElement
        });
        
        if (dropdownToggle && balanceElement && creditsElement) {
            dropdownToggle.addEventListener('show.bs.dropdown', function() {
                console.log('Admin Panel - Dropdown opened, checking if should load data...');
                console.log('Admin Panel - Balance element text:', balanceElement.textContent);
                console.log('Admin Panel - Credits element text:', creditsElement.textContent);
                
                // Only load if not already loaded
                if (balanceElement.textContent === '...' || balanceElement.textContent === 'Loading...') {
                    console.log('Admin Panel - Loading user data...');
                    loadUserData();
                } else {
                    console.log('Admin Panel - Data already loaded, skipping...');
                }
            });
        } else {
            console.error('Admin Panel - Missing dropdown elements:', {
                dropdownToggle: !!dropdownToggle,
                balanceElement: !!balanceElement,
                creditsElement: !!creditsElement
            });
        }
        
        function showLoadingDots(element) {
            let dots = 0;
            const interval = setInterval(() => {
                dots = (dots + 1) % 4;
                element.textContent = '.'.repeat(dots);
            }, 500);
            return interval;
        }
        
        function loadUserData() {
            // Show animated loading dots
            const balanceInterval = showLoadingDots(balanceElement);
            const creditsInterval = showLoadingDots(creditsElement);
            
            console.log('Loading user credits from:', '{{ url("api/user/credits") }}');
            
            // Get user credits from Laravel (not SevenLabs API)
            fetch('{{ url("api/user/credits") }}', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('API Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API Response data:', data);
                
                // Clear loading intervals
                clearInterval(balanceInterval);
                clearInterval(creditsInterval);
                
                if (data.success) {
                    // Update balance (show admin balance from SevenLabs)
                    balanceElement.textContent = data.admin_balance || 0;
                    
                    // Update credits (show user's own credits)
                    creditsElement.textContent = data.user_credits || 0;
                    
                    console.log('Updated balance:', data.admin_balance);
                    console.log('Updated credits:', data.user_credits);
                } else {
                    // Show error state
                    balanceElement.textContent = 'Error';
                    creditsElement.textContent = 'Error';
                }
            })
            .catch(error => {
                // Clear loading intervals
                clearInterval(balanceInterval);
                clearInterval(creditsInterval);
                
                console.error('Error loading user data:', error);
                balanceElement.textContent = 'Error';
                creditsElement.textContent = 'Error';
            });
        }
    });
    </script>
    @endauth

    @if (session('unauthorized'))
      <script type="text/javascript">
       swal({
         title: "{{ trans('misc.error_oops') }}",
         text: "{{ session('unauthorized') }}",
         type: "error",
         confirmButtonText: "{{ trans('users.ok') }}"
         });
         </script>
      @endif
     </body>
</html>
