@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <a class="text-reset" href="{{ url('panel/admin/plans') }}">{{ __('admin.plans') }}</a>
			<i class="bi-chevron-right me-1 fs-6"></i>
			<span class="text-muted">{{ __('admin.edit') }}</span>
			<i class="bi-chevron-right me-1 fs-6"></i>
			<span class="text-muted">{{ $plan->name }}</span>
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

      @include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-5">

					 <form method="post" action="{{ url('panel/admin/plans/update') }}" enctype="multipart/form-data">
						 @csrf

						 <input type="hidden" name="id" value="{{ $plan->id }}">

		        <div class="row mb-3">
		          <label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.name') }}</label>
		          <div class="col-sm-10">
		            <input  value="{{ $plan->name }}" required name="name" type="text" class="form-control">
		          </div>
		        </div>

						<div class="row mb-3">
		          <label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.price_per_month') }}</label>
		          <div class="col-sm-10">
		            <input  value="{{ $plan->price }}" required name="price" type="text" class="form-control isNumber" placeholder="0.00" autocomplete="off">
		          </div>
		        </div>

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.price_per_year') }}</label>
							<div class="col-sm-10">
								<input  value="{{ $plan->price_year }}" required name="price_year" type="text" class="form-control isNumber" placeholder="0.00" autocomplete="off">
							</div>
						</div>

		        <div class="row mb-3">
		          <label class="col-sm-2 col-form-labe text-lg-end">{{ __('admin.downloadable_content') }}</label>
		          <div class="col-sm-10">
		            <select name="downloadable_content" class="form-select">
									<option  @if ($plan->downloadable_content == 'images') checked @endif value="images">{{ __('admin.images') }}</option>
		           </select>
		          </div>
		        </div>

						<div class="row mb-3">
		          <label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.downloads_per_month') }}</label>
		          <div class="col-sm-10">
		            <input value="{{ $plan->downloads_per_month }}" required name="downloads_per_month" type="number" min="1" class="form-control">
		          </div>
		        </div>

						<div class="row mb-3">
		          <label class="col-sm-2 col-form-labe text-lg-end">{{ trans('admin.daily_limit_downloads') }}</label>
		          <div class="col-sm-10">
		            <select name="download_limits" class="form-select">
									@for ($i=0; $i <= 100; $i++)
										<option @if ($plan->download_limits == $i) selected @endif value="{{ $i }}">{{ $i == 0 ? trans('admin.unlimited') : $i }}</option>
									@endfor
		           </select>
		          </div>
		        </div><!-- end row -->

						<div class="row mb-3">
		          <label class="col-sm-2 col-form-labe text-lg-end">{{ trans('admin.type_license') }}</label>
		          <div class="col-sm-10">
		            <select name="license" class="form-select">
                  <option @if ($plan->license == 'regular') selected @endif value="regular">{{ trans('misc.regular') }}</option>
                  <option @if ($plan->license == 'regular_extended') selected @endif value="regular_extended">{{ trans('admin.regular_extended') }}</option>
		           </select>
		          </div>
		        </div><!-- end row -->

				<fieldset class="row mb-3">
					<legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('misc.popular') }}</legend>
					<div class="col-sm-10">
					  <div class="form-check form-switch form-switch-md">
					   <input class="form-check-input" type="checkbox" @if ($plan->popular) checked @endif name="popular" value="1" role="switch">
					 </div>
					</div>
				  </fieldset><!-- end row -->

				<fieldset class="row mb-3">
		          <legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('admin.unused_downloads_rollover') }} <i class="bi-info-circle showTooltip ms-1" title="{{ trans('misc.unused_downloads_added_next_month') }}"></i> </legend>
		          <div class="col-sm-10">
		            <div class="form-check form-switch form-switch-md">
		             <input class="form-check-input" type="checkbox" @if ($plan->unused_downloads_rollover) checked @endif name="unused_downloads_rollover" value="1" role="switch">
		           </div>
		          </div>
		        </fieldset><!-- end row -->				

				<fieldset class="row mb-3">
		          <legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('misc.enabled') }}</legend>
		          <div class="col-sm-10">
		            <div class="form-check form-switch form-switch-md">
		             <input class="form-check-input" type="checkbox" name="status" @if ($plan->status) checked="checked" @endif value="1" role="switch">
		           </div>
		          </div>
		        </fieldset><!-- end row -->

						<div class="row mb-3">
		          <div class="col-sm-10 offset-sm-2">
		            <button type="submit" class="btn btn-dark mt-3 px-5">{{ __('admin.save') }}</button>
		          </div>
		        </div>

		       </form>

				 </div><!-- card-body -->
 			</div><!-- card  -->
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection
