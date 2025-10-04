@extends('layouts.app')

@section('title') {{trans('misc.add_funds')}} -@endsection

@section('content')
<section class="section section-sm">

    <div class="container-custom container py-5">

      <div class="row">

        <div class="col-md-3">
          @include('users.navbar-settings')
        </div>

        <div class="col-md-9 mb-5 mb-lg-0">

          @if (session('error'))
       <div class="alert alert-danger alert-dismissible fade show" role="alert">
                 {{ session('error') }}

                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                   <i class="bi bi-x-lg"></i>
                 </button>
                 </div>
         @endif

          <div class="alert alert-success shadow overflow-hidden" role="alert">

            <div class="inner-wrap">
              <span>
                <h2><strong>{{Helper::amountFormatDecimal(auth()->user()->funds)}}</strong>
                  <small class="h5">{{ $settings->currency_code}}</small>
                </h2>

                <span class="w-100 d-block">
                {{trans('misc.funds_available')}}
                </span>

              </span>
            </div>

            <span class="icon-wrap"><i class="bi bi-wallet2"></i></span>

        </div><!-- /alert -->

          <form method="POST" action="{{ url('user/dashboard/add/funds') }}" id="formAddFunds">

            @csrf
            <input type="hidden" id="cardholder-name" value="{{ auth()->user()->name ?: auth()->user()->username }}"  />
						<input type="hidden" id="cardholder-email" value="{{ auth()->user()->email }}"  />

            <div class="form-group mb-4">

              <input class="form-control form-control-lg mb-2" id="onlyNumber" required name="amount" min="{{ $settings->min_deposits_amount }}" max="{{ $settings->max_deposits_amount }}" autocomplete="off" placeholder="{{ trans('misc.amount') }}" type="number">

              <p class="help-block margin-bottom-zero fee-wrap">

                <span class="d-block w-100">
                {{ trans('misc.handling_fee') }}:

                <span class="float-end"><strong>{{ $settings->currency_position == 'left' ? $settings->currency_symbol : null }}<span id="handlingFee">0</span>{{ $settings->currency_position == 'right' ? $settings->currency_symbol : null }}</strong></span>
              </span><!-- end transaction fee -->

              @if (auth()->user()->isTaxable()->count() && $settings->tax_on_wallet)

                @php
              		$number = 0;
              	@endphp

                @foreach (auth()->user()->isTaxable() as $tax)

                  @php
              			$number++;
              		@endphp

                <span class="d-block w-100 isTaxableWallet percentageAppliedTaxWallet{{$number}}" data="{{ $tax->percentage }}">
                  {{ $tax->name }} {{ $tax->percentage }}%:

                  <span class="float-end">
                  <strong>{{ $settings->currency_position == 'left' ? $settings->currency_symbol : null }}<span class="percentageTax{{$number}}">0</span>{{ $settings->currency_position == 'right' ? $settings->currency_symbol : null }}</strong>
                </span>
              </span>
                @endforeach

  						@endif

                <span class="d-block w-100">
                  {{ trans('misc.total') }}:

                  <span class="float-end">
                  <strong>{{ $settings->currency_position == 'left' ? $settings->currency_symbol : null }}<span id="total">0</span>{{ $settings->currency_position == 'right' ? $settings->currency_symbol : null }}</strong>
                </span>
              </span><!-- end total -->
              </p>

            </div><!-- End form-group -->

            @foreach ($paymentGateways as $payment)
              <div class="form-check custom-radio mb-3">
                <input name="payment_gateway" required value="{{$payment->id}}" id="tip_radio{{$payment->id}}" @if ($paymentGateways->count() == 1) checked @endif class="form-check-input {{ $payment->type == 'bank' ? 'bankTriggerClass' : null }}" type="radio">
                <label class="form-check-label" for="tip_radio{{$payment->id}}">
                  <span><img class="me-1 rounded" src="{{ url('public/img/payments', $payment->logo) }}" width="20" /> <strong>{{ $payment->type == 'bank' ? __('misc.bank_transfer') : $payment->name }}</strong></span>
                  <small class="w-100 d-block">
                    * {{ trans('misc.handling_fee') }}: {{ $payment->fee != 0.00 ? $payment->fee.'%' : null }} {{ $payment->fee_cents != 0.00 ? '+ '. Helper::amountFormatDecimal($payment->fee_cents) : null }}
                  </small>
                </label>
              </div>

              @if ($payment->type == 'bank')
                <div class="btn-block @if ($paymentGateways->count() != 1) display-none @endif" id="bankTransferBox">
                  <div class="alert alert-default border">
                  <h5 class="font-weight-bold"><i class="fa fa-university me-1 icon-sm-radio"></i> {{__('misc.make_payment_bank')}}</h5>
                  <ul class="list-unstyled">
                      <li>
                        {!!nl2br($payment->bank_info)!!}

                        <hr />
                        <span class="d-block w-100 mt-2">
                        {{ __('misc.total') }}: <strong>{{ $settings->currency_position == 'left'  ? $settings->currency_symbol : (($settings->currency_position == 'left_space') ? $settings->currency_symbol.' ' : null) }}<span id="total2">0</span>{{ $settings->currency_position == 'right' ? $settings->currency_symbol : (($settings->currency_position == 'right_space') ? ' '.$settings->currency_symbol : null) }}</strong>
                        <span>

                      </li>
                  </ul>
                </div>

                <div class="mb-3 text-center">
                  <span class="d-block mb-2 text-break" id="previewImage"></span>
                    <input type="file" name="image" id="fileBankTransfer" accept="image/*">
                    <button class="btn w-100 py-3 color-link d-block btn-outline-custom btn-outline-transparent mb-2 border-dashed" onclick="$('#fileBankTransfer').trigger('click');" type="button" id="btnFilePhoto">
                      {{__('misc.upload_screenshot')}} (JPG, PNG, GIF)
                    </button>

                  <small class="btn-block">{{__('misc.info_bank_transfer')}} - ({{ __('misc.maximum') }} {{Helper::formatBytes(1048576)}}) </small>
                </div>
                </div><!-- Alert -->
              @endif

            @endforeach

            <div class="alert alert-danger display-none" id="errorAddFunds">
                <ul class="list-unstyled m-0" id="showErrorsFunds"></ul>
              </div>

            <button class="btn btn-lg btn-custom d-block w-100 mt-4" id="addFundsBtn" type="submit"><i></i> {{trans('misc.add_funds')}}</button>
          </form>

          @if ($data->count() != 0)
          <h6 class="text-center mt-5 font-weight-light">{{ __('misc.deposits_history') }}</h6>

          <div class="card shadow-sm">
            <div class="table-responsive">
              <table class="table m-0">
                <thead>
                  <th scope="col">ID</th>
                  <th scope="col">{{ trans('admin.amount') }}</th>
                  <th scope="col">{{ trans('misc.payment_gateway') }}</th>
                  <th scope="col">{{ trans('admin.date') }}</th>
                  <th scope="col">{{ trans('admin.status') }}</th>
                  <th> {{trans('misc.invoice')}}</th>
                </thead>

                <tbody>
                  @foreach ($data as $deposit)

                    <tr>
                      <td>{{ str_pad($deposit->id, 4, "0", STR_PAD_LEFT) }}</td>
                      <td>{{ Helper::amountFormat($deposit->amount) }}</td>
                      <td>{{ $deposit->payment_gateway == 'Bank' ? __('misc.bank_transfer') : $deposit->payment_gateway }}</td>
                      <td>{{ date('d M, Y', strtotime($deposit->date)) }}</td>
                       <td>
                        <small class="badge rounded-pill bg-{{ $deposit->status == 'pending' ? 'warning' : 'success'}} text-uppercase">
                          {{ $deposit->status == 'pending' ? __('admin.pending') : __('misc.success') }}
                        </small>
                      </td>

                       <td>
                         @if ($deposit->invoice())
                         <a href="{{url('invoice', $deposit->invoice()->id)}}" target="_blank"><i class="bi-receipt"></i> {{trans('misc.invoice')}}</a>

                       @else
                       {{trans('misc.not_available')}}
                         @endif
                         </td>
                    </tr><!-- /.TR -->
                    @endforeach
                </tbody>
              </table>
            </div><!-- table-responsive -->
          </div><!-- card -->

          @if ($data->hasPages())
  			    	<div class="mt-3">
                {{ $data->links() }}
              </div>
  			    	@endif

        @endif

        </div><!-- end col-md-6 -->
      </div>
    </div>
  </section>
@endsection

@section('javascript')
  <script src="{{ asset('public/js/add-funds.js') }}?v={{$settings->version}}"></script>

  <script src='https://js.paystack.co/v1/inline.js'></script>
  <script src='https://checkout.razorpay.com/v1/checkout.js'></script>

<script type="text/javascript">
@if($settings->currency_code == 'JPY')
  $decimal = 0;
  @else
  $decimal = 2;
  @endif

  function toFixed(number, decimals) {
        var x = Math.pow(10, Number(decimals) + 1);
        return (Number(number) + (1 / x)).toFixed(decimals);
      }

  $('input[name=payment_gateway]').on('click', function() {

    var valueOriginal = $('#onlyNumber').val();
    var value = parseFloat($('#onlyNumber').val());
    var element = $(this).val();

    //==== Start Taxes
    var taxes = $('span.isTaxableWallet').length;
    var totalTax = 0;

    if (valueOriginal.length == 0
				|| valueOriginal == ''
				|| value < {{ $settings->min_deposits_amount }}
				|| value > {{$settings->max_deposits_amount}}
      ) {
        // Reset
  			for (var i = 1; i <= taxes; i++) {
  				$('.percentageTax'+i).html('0');
  			}
        $('#handlingFee, #total, #total2').html('0');
      } else {
        // Taxes
        for (var i = 1; i <= taxes; i++) {
          var percentage = $('.percentageAppliedTaxWallet'+i).attr('data');
          var valueFinal = (value * percentage / 100);
          $('.percentageTax'+i).html(toFixed(valueFinal, $decimal));
          totalTax += valueFinal;
        }
        var totalTaxes = (Math.round(totalTax * 100) / 100).toFixed($decimal);
      }
      //==== End Taxes

    if (element != ''
        && value <= {{ $settings->max_deposits_amount }}
        && value >= {{ $settings->min_deposits_amount }}
        && valueOriginal != ''
      ) {
      // Fees
      switch (parseFloat(element)) {
        @foreach ($paymentGateways as $payment)
        case {{$payment->id}}:
          $fee   = {{$payment->fee}};
          $cents =  {{$payment->fee_cents}};
          break;
        @endforeach
      }

      var amount = (value * $fee / 100) + $cents;
      var amountFinal = toFixed(amount, $decimal);

      var total = (parseFloat(value) + parseFloat(amountFinal) + parseFloat(totalTaxes));

      if (valueOriginal.length != 0
  				|| valueOriginal != ''
  				|| value >= {{ $settings->min_deposits_amount }}
  				|| value <= {{$settings->max_deposits_amount}}
        ) {
        $('#handlingFee').html(amountFinal);
        $('#total, #total2').html(total.toFixed($decimal));
      }
    }

});

//<-------- * TRIM * ----------->
$('#onlyNumber').on('keyup', function() {

    var valueOriginal = $(this).val();
    var value = parseFloat($(this).val());
    var paymentGateway = $('input[name=payment_gateway]:checked').val();

    if (value > {{ $settings->max_deposits_amount }} || valueOriginal.length == 0) {
      $('#handlingFee').html('0');
      $('#total, #total2').html('0');
    }

    //==== Start Taxes
    var taxes = $('span.isTaxableWallet').length;
    var totalTax = 0;

    if (valueOriginal.length == 0
				|| valueOriginal == ''
				|| value < {{ $settings->min_deposits_amount }}
				|| value > {{$settings->max_deposits_amount}}
      ) {
        // Reset
  			for (var i = 1; i <= taxes; i++) {
  				$('.percentageTax'+i).html('0');
  			}
        $('#handlingFee, #total, #total2').html('0');
      } else {
        // Taxes
        for (var i = 1; i <= taxes; i++) {
          var percentage = $('.percentageAppliedTaxWallet'+i).attr('data');
          var valueFinal = (value * percentage / 100);
          $('.percentageTax'+i).html(toFixed(valueFinal, $decimal));
          totalTax += valueFinal;
        }
        var totalTaxes = (Math.round(totalTax * 100) / 100).toFixed($decimal);
      }

    if (paymentGateway
        && value <= {{ $settings->max_deposits_amount }}
        && value >= {{ $settings->min_deposits_amount }}
        && valueOriginal != ''
      ) {

      switch(parseFloat(paymentGateway)) {
        @foreach ($paymentGateways as $payment)
        case {{$payment->id}}:
          $fee   = {{$payment->fee}};
          $cents =  {{$payment->fee_cents}};
          break;
        @endforeach
      }

      var amount = (value * $fee / 100) + $cents;
      var amountFinal = toFixed(amount, $decimal);

      var total = (parseFloat(value) + parseFloat(amountFinal) + parseFloat(totalTaxes));

      if (valueOriginal.length != 0
  				|| valueOriginal != ''
  				|| value >= {{ $settings->min_deposits_amount }}
  				|| value <= {{$settings->max_deposits_amount}}
        ) {
        $('#handlingFee').html(amountFinal);
        $('#total, #total2').html(total.toFixed($decimal));
      } else {
        $('#handlingFee, #total, #total2').html('0');
        }
    }
});

@if (session('payment_process'))
   swal({
     html:true,
     title: "{{ trans('misc.congratulations') }}",
     text: "{!! trans('misc.payment_process_wallet') !!}",
     type: "success",
     confirmButtonText: "{{ trans('users.ok') }}"
     });
  @endif

</script>
@endsection
