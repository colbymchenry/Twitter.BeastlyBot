@php
    $enabled = true;
@endphp


<header class="slidePanel-header bg-blue-500">
    <div class="slidePanel-actions" aria-label="actions" role="group">
        <button type="button" class="btn btn-icon btn-pure btn-inverse slidePanel-close actions-top icon wb-close"
                aria-hidden="true" id="back-btn"></button>
    </div>
    <h1>Pricing</h1>
</header>

@if(auth()->user()->StripeConnect()->express_id == null)
<div class="page-header my-10">

    <div class="page-header-actions add-pulse">
        <a class="btn btn-primary btn-round"
            href="{{ env('STRIPE_CONNECT_URL') }}">
            Connect Stripe
            <i class="icon-stripe ml-2" aria-hidden="true"></i>
        </a>
    </div>
</div>
@endif

<div class="site-sidebar-tab-content put-long tab-content" id="slider-div">
    <div class="tab-pane fade active show" id="sidebar-userlist">
        <div>
           <div>
                <div class="row">
                    <div class="col-12">
                    <h5>Subscription Prices</h5>
                    <div class="card">
                        <div class="card-body">
                            <div class="row no-space text-center">
                                @for($i = 0; $i < 13; $i++)
                                @if($i === 1 || $i === 3 || $i === 6 || $i === 12)
                                <div class="col-6 col-sm-3">
                                    <div class="card border-0 vertical-align h-100">
                                    <div class="vertical-align-middle font-size-16">
                                        <div class="d-block">
                                            <div class="input-group w-120 mx-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">$</span>
                                                </div>
                                                <input id="price_{{ $i }}m" type="text" class="form-control"
                                                    placeholder="0.00" @if(array_key_exists($i, $prices)) value="{{ $prices[$i] }}" @endif autocomplete="off">
                                            </div>
                                        </div>
                                        <i class="wb-triangle-down font-size-24 mb-10 blue-600"></i>
                                        <div>
                                        <span class="font-size-12">{{ $i }} month</span>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                                @endif
                                @endfor

                            
                                <div class="col-12">
                                    <button id="prices_btn" type="button" class="btn btn-dark btn-lg btn-block @if(!$enabled) disabled @endif"
                                        onclick="updatePrices()" @if(!$enabled) disabled @endif>Update Prices
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>



            </div>
            <div>
                <div class="row pb-30">
                    <div class="col-12">
                        <h5>Feed Description</h5>
                        <div class="card">
                            <div class="card-body">
                                <div class="row no-space text-center">
                                    <div class="col-12">
                                            <textarea id='product-description' class="lit-group-item form-control" placeholder="These awesome perks..."
                                                    @if(!$enabled) disabled
                                                    @endif>{{ auth()->user()->getTwitterAccount()->description }}</textarea>
                                        <button type="button" class="btn btn-block mt-10 btn-dark btn-lg @if(!$enabled) disabled @endif" id="desc-btn"
                                            @if(!$enabled) disabled @else onclick="updateAccountDescription()" @endif>Update Description
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
           <div>
                <div class="row pb-30">
                    <div class="col-12">
                        @if(isset($shop_url))
                        <a href="/shop/{{ $shop_url }}" class="btn float-right btn-primary d-none" id="btn_visit-shop">Visit shop</a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script type="text/javascript">

    function updateAccountDescription() {
        
    }

    function updatePrices() {
        Toast.fire({
            title: 'Processing....',
            text: '',
            showCancelButton: false,
            showConfirmButton: false,
            allowOutsideClick: () => !Toast.isLoading(),
            //target: document.getElementById('slider-div')
        });
        Toast.showLoading();
        $.ajax({
            url: '/plan',
            type: 'POST',
            data: {
                'action': 'update',
                'product_type': 'twitter',
                'interval': 'month',
                'interval_cycle': 1,
                'price': $('#price_1m').val(),
                _token: '{{ csrf_token() }}'
            },
        }).done(function (msg, enabled) {
            $.ajax({
                url: '/plan',
                type: 'POST',
                data: {
                    'action': 'update',
                    'product_type': 'twitter',
                    'interval': 'month',
                    'interval_cycle': 3,
                    'price': $('#price_3m').val(),
                    _token: '{{ csrf_token() }}'
                },
            }).done(function (msg, enabled) {
                $.ajax({
                    url: '/plan',
                    type: 'POST',
                    data: {
                        'action': 'update',
                        'product_type': 'twitter',
                        'interval': 'month',
                        'interval_cycle': 6,
                        'price': $('#price_6m').val(),
                        _token: '{{ csrf_token() }}'
                    },
                }).done(function (msg, enabled) {
                    $.ajax({
                        url: '/plan',
                        type: 'POST',
                        data: {
                            'action': 'update',
                            'product_type': 'twitter',
                            'interval': 'month',
                            'interval_cycle': 12,
                            'price': $('#price_12m').val(),
                            _token: '{{ csrf_token() }}'
                        },
                    }).done(function (msg, enabled) {
                        if (msg['success']) {
                            Toast.fire({
                                title: 'Success!',
                                text: msg['msg'],
                                type: 'success',
                            });
                        } else {
                            Toast.fire({
                                title: 'Success',
                                text: msg['msg'],
                                type: 'warning',
                            });
                        }
                    });
                });
            });
        });
    }

</script>
 
@include('partials/clear_script')
