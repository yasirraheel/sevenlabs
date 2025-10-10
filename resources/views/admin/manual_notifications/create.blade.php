@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
	<a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
	<i class="bi-chevron-right me-1 fs-6"></i>
	<a class="text-reset" href="{{ route('admin.manual_notifications.index') }}">{{ __('admin.manual_notifications') }}</a>
	<i class="bi-chevron-right me-1 fs-6"></i>
	<span class="text-muted">{{ __('admin.add_notification') }}</span>
</h5>

<div class="content">
	<div class="row">
		<div class="col-lg-12">
			@include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-header bg-white">
					<h6 class="mb-0">{{ __('admin.add_notification') }}</h6>
				</div>

				<div class="card-body p-lg-5">
					<form method="POST" action="{{ route('admin.manual_notifications.store') }}" 
						enctype="multipart/form-data">
						@csrf

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.title') }} <span class="text-danger">*</span></label>
							<div class="col-sm-10">
								<input type="text" name="title" value="{{ old('title') }}" 
									class="form-control @error('title') is-invalid @enderror" 
									placeholder="{{ __('admin.enter_notification_title') }}" required>
								@error('title')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.message') }} <span class="text-danger">*</span></label>
							<div class="col-sm-10">
								<textarea name="message" rows="5" 
									class="form-control @error('message') is-invalid @enderror" 
									placeholder="{{ __('admin.enter_notification_message') }}" required>{{ old('message') }}</textarea>
								@error('message')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>

						<div class="row mb-3">
							<label class="col-sm-2 col-form-label text-lg-end">{{ __('admin.image') }}</label>
							<div class="col-sm-10">
								<input type="file" name="image" 
									class="form-control @error('image') is-invalid @enderror" 
									accept="image/*">
								<div class="form-text">{{ __('admin.image_help_text') }}</div>
								@error('image')
								<div class="invalid-feedback">{{ $message }}</div>
								@enderror
							</div>
						</div>

						<fieldset class="row mb-3">
							<legend class="col-form-label col-sm-2 pt-0 text-lg-end">{{ __('admin.status') }}</legend>
							<div class="col-sm-10">
								<div class="form-check form-switch form-switch-md">
									<input class="form-check-input" type="checkbox" name="is_active" value="1" 
										{{ old('is_active', true) ? 'checked' : '' }} role="switch">
									<label class="form-check-label" for="is_active">
										{{ __('admin.active') }}
									</label>
								</div>
							</div>
						</fieldset>

						<div class="row mb-3">
							<div class="col-sm-10 offset-sm-2">
								<button type="submit" class="btn btn-primary px-4">
									<i class="bi bi-check-lg me-1"></i> {{ __('admin.create_notification') }}
								</button>
								<a href="{{ route('admin.manual_notifications.index') }}" class="btn btn-secondary px-4 ms-2">
									<i class="bi bi-arrow-left me-1"></i> {{ __('admin.cancel') }}
								</a>
							</div>
						</div>

					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
