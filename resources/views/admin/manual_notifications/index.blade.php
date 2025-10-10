@extends('admin.layout')

@section('content')
<h5 class="mb-4 fw-light">
	<a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
	<i class="bi-chevron-right me-1 fs-6"></i>
	<span class="text-muted">{{ __('admin.manual_notifications') }}</span>
</h5>

<div class="content">
	<div class="row">
		<div class="col-lg-12">
			@if (session('success_message'))
			<div class="alert alert-success alert-dismissible fade show" role="alert">
				<i class="bi bi-check2 me-1"></i> {{ session('success_message') }}

				<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
					<i class="bi bi-x-lg"></i>
				</button>
			</div>
			@endif

			@include('errors.errors-forms')

			<div class="card shadow-custom border-0">
				<div class="card-header bg-white">
					<div class="d-flex justify-content-between align-items-center">
						<h6 class="mb-0">{{ __('admin.manual_notifications') }}</h6>
						<a href="{{ route('admin.manual_notifications.create') }}" class="btn btn-primary btn-sm">
							<i class="bi bi-plus-lg me-1"></i> {{ __('admin.add_notification') }}
						</a>
					</div>
				</div>

				<div class="card-body p-0">
					@if($notifications->count() > 0)
					<div class="table-responsive">
						<table class="table table-hover mb-0">
							<thead class="table-light">
								<tr>
									<th class="border-0">{{ __('admin.image') }}</th>
									<th class="border-0">{{ __('admin.title') }}</th>
									<th class="border-0">{{ __('admin.message') }}</th>
									<th class="border-0">{{ __('admin.status') }}</th>
									<th class="border-0">{{ __('admin.created_at') }}</th>
									<th class="border-0 text-center">{{ __('admin.actions') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach($notifications as $notification)
								<tr>
									<td>
										@if($notification->image)
										<img src="{{ $notification->image_url }}" alt="{{ $notification->title }}" 
											class="rounded" style="width: 50px; height: 50px; object-fit: cover;"
											onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
										@endif
										@if(!$notification->image)
										<div class="bg-light rounded d-flex align-items-center justify-content-center" 
											style="width: 50px; height: 50px;">
											<i class="bi bi-image text-muted"></i>
										</div>
										@else
										<div class="bg-light rounded d-flex align-items-center justify-content-center" 
											style="width: 50px; height: 50px; display: none;">
											<i class="bi bi-image text-muted"></i>
										</div>
										@endif
									</td>
									<td>
										<strong>{{ $notification->title }}</strong>
									</td>
									<td>
										<span class="text-muted">{{ Str::limit($notification->message, 50) }}</span>
									</td>
									<td>
										@if($notification->is_active)
										<span class="badge bg-success">{{ __('admin.active') }}</span>
										@else
										<span class="badge bg-secondary">{{ __('admin.inactive') }}</span>
										@endif
									</td>
									<td>
										<span class="text-muted">{{ $notification->created_at->format('M d, Y H:i') }}</span>
									</td>
									<td class="text-center">
										<div class="btn-group" role="group">
											<a href="{{ route('admin.manual_notifications.show', $notification) }}" 
												class="btn btn-outline-primary btn-sm" title="{{ __('admin.view') }}">
												<i class="bi bi-eye"></i>
											</a>
											<a href="{{ route('admin.manual_notifications.edit', $notification) }}" 
												class="btn btn-outline-secondary btn-sm" title="{{ __('admin.edit') }}">
												<i class="bi bi-pencil"></i>
											</a>
											<form action="{{ route('admin.manual_notifications.toggle_status', $notification) }}" 
												method="POST" class="d-inline">
												@csrf
												@method('PATCH')
												<button type="submit" class="btn btn-outline-{{ $notification->is_active ? 'warning' : 'success' }} btn-sm" 
													title="{{ $notification->is_active ? __('admin.deactivate') : __('admin.activate') }}">
													<i class="bi bi-{{ $notification->is_active ? 'pause' : 'play' }}"></i>
												</button>
											</form>
											<form action="{{ route('admin.manual_notifications.destroy', $notification) }}" 
												method="POST" class="d-inline" 
												onsubmit="return confirm('{{ __('admin.confirm_delete_notification') }}')">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm" title="{{ __('admin.delete') }}">
													<i class="bi bi-trash"></i>
												</button>
											</form>
										</div>
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>

					@if($notifications->hasPages())
					<div class="card-footer bg-white">
						{{ $notifications->links() }}
					</div>
					@endif

					@else
					<div class="text-center py-5">
						<i class="bi bi-bell-slash display-1 text-muted"></i>
						<h5 class="mt-3 text-muted">{{ __('admin.no_notifications_found') }}</h5>
						<p class="text-muted">{{ __('admin.create_first_notification') }}</p>
						<a href="{{ route('admin.manual_notifications.create') }}" class="btn btn-primary">
							<i class="bi bi-plus-lg me-1"></i> {{ __('admin.add_notification') }}
						</a>
					</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
