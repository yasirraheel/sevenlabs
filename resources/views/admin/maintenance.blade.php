@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('admin.maintenance_mode') }}</span>
  </h5>

<div class="content">
	<div class="row">

		<div class="col-lg-12">

			@if (session('success_message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check2 me-1"></i>	{{ session('success_message') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                  <i class="bi bi-x-lg"></i>
                </button>
                </div>
              @endif

			<div class="card shadow-custom border-0">
				<div class="card-body p-lg-5">

					 <form method="POST" action="{{ url('panel/admin/maintenance') }}" enctype="multipart/form-data">
						 @csrf

						 <fieldset class="row mb-3">
 		          <legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('admin.maintenance_mode') }}</legend>
 		          <div class="col-sm-10">
 		            <div class="form-check form-switch form-switch-md">
 		             <input class="form-check-input" type="checkbox" name="maintenance_mode" @if ($settings->maintenance_mode == 'on') checked="checked" @endif value="on" role="switch">
 		           </div>
					<small class="d-block w-100 float-start mt-2">
						<i class="bi-exclamation-triangle-fill me-1 text-warning"></i>
						{{ __('misc.alert_maintenance_mode') }}
					  </small>
 		          </div>
 		        </fieldset><!-- end row -->

						<div class="row mb-3">
		          <div class="col-sm-10 offset-sm-2">
		            <button type="submit" class="btn btn-dark mt-3 px-5">{{ __('admin.save') }}</button>

					<a href="{{ url('panel/admin/clear-cache') }}" class="btn btn-link text-reset mt-3 px-3 e-none text-decoration-none">
						<i class="bi-trash3-fill me-1"></i> {{ __('admin.clear_cache') }}

						@if (file_exists(storage_path("logs".DIRECTORY_SEPARATOR."laravel.log")))
						<small class="w-100 d-block">
							(Log File: {{ Helper::formatBytes(filesize(storage_path("logs".DIRECTORY_SEPARATOR."laravel.log"))) }})
						</small>
						@endif
					</a>
		          </div>
		        </div>

		       </form>

				 </div><!-- card-body -->
 			</div><!-- card  -->
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection

@section('javascript')

<script type="text/javascript"></script>
  @endsection
