@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        {{ __('admin.deposits') }}
  </h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              @endif

                    <!-- Filter Tabs -->
                    <ul class="nav nav-tabs mb-4" id="depositTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                All Deposits ({{ $allDeposits->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                Pending ({{ $pendingDeposits->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button" role="tab">
                                Approved ({{ $approvedDeposits->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab">
                                Rejected ({{ $rejectedDeposits->count() }})
                            </button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="depositTabsContent">
                        <!-- All Deposits -->
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            @include('admin.partials.deposits-table', ['deposits' => $allDeposits])
                        </div>

                        <!-- Pending Deposits -->
                        <div class="tab-pane fade" id="pending" role="tabpanel">
                            @include('admin.partials.deposits-table', ['deposits' => $pendingDeposits])
                        </div>

                        <!-- Approved Deposits -->
                        <div class="tab-pane fade" id="approved" role="tabpanel">
                            @include('admin.partials.deposits-table', ['deposits' => $approvedDeposits])
                        </div>

                        <!-- Rejected Deposits -->
                        <div class="tab-pane fade" id="rejected" role="tabpanel">
                            @include('admin.partials.deposits-table', ['deposits' => $rejectedDeposits])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deposit Action Modal -->
<div class="modal fade" id="depositActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depositActionModalLabel">Deposit Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositActionForm" method="POST" target="_self">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="deposit_id" name="deposit_id">
                    <input type="hidden" id="action_type" name="action_type">

                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about this deposit..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitActionBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Proof Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="proof-content">
                    <!-- Payment proof will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function approveDeposit(id) {
    document.getElementById('deposit_id').value = id;
    document.getElementById('action_type').value = 'approve';
    document.getElementById('depositActionModalLabel').textContent = 'Approve Deposit';
    document.getElementById('submitActionBtn').textContent = 'Approve';
    document.getElementById('submitActionBtn').className = 'btn btn-success';
    document.getElementById('depositActionForm').action = '{{ url("panel/admin/deposits/approve") }}';

    const modal = new bootstrap.Modal(document.getElementById('depositActionModal'));
    modal.show();
}

function rejectDeposit(id) {
    document.getElementById('deposit_id').value = id;
    document.getElementById('action_type').value = 'reject';
    document.getElementById('depositActionModalLabel').textContent = 'Reject Deposit';
    document.getElementById('submitActionBtn').textContent = 'Reject';
    document.getElementById('submitActionBtn').className = 'btn btn-danger';
    document.getElementById('depositActionForm').action = '{{ url("panel/admin/deposits/reject") }}';

    const modal = new bootstrap.Modal(document.getElementById('depositActionModal'));
    modal.show();
}

function viewPaymentProof(proofPath) {
    const proofContent = document.getElementById('proof-content');

    // Check if it's an image or PDF
    const extension = proofPath.split('.').pop().toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        proofContent.innerHTML = `<img src="{{ url('public/deposits') }}/${proofPath}" class="img-fluid" alt="Payment Proof">`;
    } else if (extension === 'pdf') {
        proofContent.innerHTML = `
            <iframe src="{{ url('public/deposits') }}/${proofPath}" width="100%" height="500px" style="border: none;"></iframe>
            <div class="mt-3">
                <a href="{{ url('public/deposits') }}/${proofPath}" target="_blank" class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Download PDF
                </a>
            </div>
        `;
    } else {
        proofContent.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                File type not supported for preview.
                <a href="{{ url('public/deposits') }}/${proofPath}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                    <i class="bi bi-download me-1"></i>Download
                </a>
            </div>
        `;
    }

    const modal = new bootstrap.Modal(document.getElementById('paymentProofModal'));
    modal.show();
}
</script>
@endsection
