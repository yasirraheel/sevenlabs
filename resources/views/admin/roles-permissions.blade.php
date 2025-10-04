@extends('admin.layout')

@section('content')
	<h5 class="mb-4 fw-light">
    <a class="text-reset" href="{{ url('panel/admin') }}">{{ __('admin.dashboard') }}</a>
      <i class="bi-chevron-right me-1 fs-6"></i>
      <span class="text-muted">{{ __('admin.role_and_permissions') }}</span>

			<a href="{{ url('panel/admin/roles-and-permissions/create') }}" class="btn btn-sm btn-dark float-lg-end mt-1 mt-lg-0">
				<i class="bi-plus-lg"></i> {{ trans('misc.add_new') }}
			</a>
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
				<div class="card-body p-lg-4">

					<div class="table-responsive p-0">
						<table class="table table-hover">
						 <tbody>

							@if ($roles->count() !=  0)
								 <tr>
										<th class="active">{{ trans('admin.name') }}</th>
										<th class="active">{{ trans('admin.permissions') }}</th>
										<th class="active">{{ trans('admin.actions') }}</th>
									</tr>

								@foreach ($roles as $role)
									<tr>
										<td>{{ $role->name }}</td>
										<td class="text-break">
											@foreach (explode(',', $role->permissions) as $permission)
												<small class="badge rounded-pill bg-primary text-capitalize">
													{{ __('admin.'.$permission) }}
												</small>
											@endforeach
										</td>
										<td>

											@if ($role->editable)
											<a href="{{ url('panel/admin/roles-and-permissions/edit', $role->id) }}" class="text-reset fs-5 me-2">
												<i class="far fa-edit"></i>
											</a>

											<form action="{{ url('panel/admin/roles-and-permissions/delete', $role->id) }}" method="POST" class="d-inline-block align-top">
												@csrf
												<button type="button" class="btn btn-link text-danger e-none fs-5 p-0 actionDelete">
													<i class="bi-trash-fill"></i>
												</button>
											</form>
										@else
											--------
										@endif
										</td>

									</tr><!-- /.TR -->
									@endforeach

									@else
										<h5 class="text-center p-5 text-muted fw-light m-0">{{ trans('misc.no_results_found') }}</h5>
									@endif

								</tbody>
								</table>
							</div><!-- /.box-body -->

				 </div><!-- card-body -->
 			</div><!-- card  -->
 		</div><!-- col-lg-12 -->

	</div><!-- end row -->
</div><!-- end content -->
@endsection
