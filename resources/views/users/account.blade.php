@extends('layouts.app')

@section('title') {{ trans('users.account_settings') }} - @endsection

@section('content')
<section class="section section-sm">

<div class="container-custom container pt-5">
<div class="row">

  <div class="col-md-3">
    @include('users.navbar-settings')
  </div>

			<!-- Col MD -->
		<div class="col-md-9">

			@if (session('notification'))
			<div class="alert alert-success alert-dismissible fade show" role="alert">
            	<i class="bi bi-check2 me-1"></i>	{{ session('notification') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                  <i class="bi bi-x-lg"></i>
                </button>
            		</div>
            	@endif

			@include('errors.errors-forms')

      <h5 class="mb-4">{{ trans('users.account_settings') }}</h5>

		<!-- ***** FORM ***** -->
       <form action="{{ url('account') }}" method="post">

        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <div class="row">
        	<div class="col-md-6">
            <div class="form-floating mb-3">
             <input type="text" required class="form-control" id="inputname" value="{{auth()->user()->name}}" name="full_name" placeholder="{{ trans('misc.full_name_misc') }}">
             <label for="inputname">{{ trans('misc.full_name_misc') }}</label>
           </div>
           </div><!-- End Col MD-->

            <div class="col-md-6">
              <div class="form-floating mb-3">
               <input type="email" required class="form-control" id="inputemail" value="{{auth()->user()->email}}" name="email" placeholder="{{ trans('auth.email') }}">
               <label for="inputemail">{{ trans('auth.email') }}</label>
             </div>
            </div><!-- End Col MD-->

        </div><!-- End row -->

			<div class="row">

				<div class="col-md-6">
          <div class="form-floating mb-3">
           <input type="text" required class="form-control" id="inputusername" value="{{auth()->user()->username}}" name="username" placeholder="{{ trans('misc.username_misc') }}">
           <label for="inputusername">{{ trans('misc.username_misc') }}</label>
         </div>
				</div><!-- End Col MD-->

				<div class="col-md-6">

          <div class="form-floating mb-3">
          <input type="text" readonly class="form-control" id="inputCity" value="{{auth()->user()->city}}" placeholder="City">
          <label for="inputCity">City</label>
        </div>

				</div><!-- End Col MD-->
			</div><!-- End row -->

			<div class="row">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="text" class="form-control" id="inputaccount_no" value="{{auth()->user()->account_no}}" name="account_no" placeholder="Account No">
						<label for="inputaccount_no">Account No</label>
					</div>
				</div><!-- End Col MD-->

				<div class="col-md-6">
					<!-- Empty column for balance -->
				</div><!-- End Col MD-->
			</div><!-- End row -->

     <hr class="my-4">
     <h6 class="mb-3">{{ trans('auth.password') }}</h6>

			<div class="row">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="password" class="form-control" id="input-oldpassword" name="old_password" placeholder="{{ trans('misc.old_password') }}">
						<label for="input-oldpassword">{{ trans('misc.old_password') }}</label>
					</div>
				</div><!-- End Col MD-->

				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="password" class="form-control" id="input-password" name="password" placeholder="{{ trans('misc.new_password') }}">
						<label for="input-password">{{ trans('misc.new_password') }}</label>
					</div>
				</div><!-- End Col MD-->
			</div><!-- End row -->

			<div class="row">
				<div class="col-md-6">
					<div class="form-floating mb-3">
						<input type="password" class="form-control" id="input-password-confirm" name="password_confirmation" placeholder="{{ trans('auth.confirm_password') }}">
						<label for="input-password-confirm">{{ trans('auth.confirm_password') }}</label>
					</div>
				</div><!-- End Col MD-->

				<div class="col-md-6">
					<!-- Empty column for balance -->
				</div><!-- End Col MD-->
			</div><!-- End row -->

    <div class="form-check form-switch form-switch-md mb-3">
      <input class="form-check-input" @if (auth()->user()->two_factor_auth == 'yes') checked @endif name="two_factor_auth" type="checkbox" value="yes" id="flexSwitchCheckDefault">
      <label class="form-check-label" for="flexSwitchCheckDefault">{{ trans('misc.two_step_auth') }} <i class="bi bi-info-circle ms-1 text-muted showTooltip" title="{{ trans('misc.two_step_auth_info') }}"></i></label>
    </div>

           <button type="submit" id="buttonSubmit" class="btn w-100 btn-lg btn-custom">{{ trans('misc.save_changes') }}</button>

         @if (auth()->id() != 1)
           <div class="d-block text-center mt-3">
           		<a href="{{url('account/delete')}}" class="text-danger">{{trans('users.delete_account')}}</a>
           </div>
           @endif
       </form><!-- ***** END FORM ***** -->

  </div><!-- /COL MD -->
  </div><!-- row -->
</div><!-- container -->
</section>
@endsection

@section('javascript')
<script type="text/javascript">

$('#authorExclusive').on('change', function() {
  if ($(this).val() == 'yes') {
    $('#percentage').html('* {{ trans('misc.user_gain', ['percentage' => (100 - $settings->fee_commission)]) }}');

  } else {
      $('#percentage').html('* {{ trans('misc.user_gain', ['percentage' => (100 - $settings->fee_commission_non_exclusive)]) }}');
  }
});

</script>
@endsection
