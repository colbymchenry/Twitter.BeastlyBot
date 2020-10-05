@extends('layouts.app-zero2')

@section('metadata')
    <title>{{ $twitter_account->screen_name }} | Shop</title>
    <meta name="description"
          content="{{ $twitter_account->screen_name }}"> <!-- server description -->
    <meta name="keywords" content="{{ $twitter_account->screen_name }}, Twitter, Shop, Beastly, Bot"> <!-- server name -->
    <meta name="author" content="BeastlyBot">

@endsection

@section('content')

@if(auth()->user()->getTwitterAccount()->twitter_id == $twitter_account->twitter_id)
    @if((!$twitter_store->live) || $owner_array->error == '2')
        <div class="bg-dark-4 text-white text-center font-size-16 font-weight-500 w-200 mx-auto card m-0 mb-30">
            <a class="card-body p-5 text-white" href="/dashboard{{ (!auth()->user()->canAcceptPayments()) ? '#ready' : '' }}">
            Store mode: <span class="btn btn-primary btn-sm font-size-14 ml-2">Test</span>
            </a>
        </div>
    @else
    <div class="bg-dark-4 text-white text-center font-size-16 font-weight-500 w-200 mx-auto card m-0 mb-30">
            <a class="card-body p-5 text-white" @if($owner_array->error != '1')href="/dashboard" @else href="/dashboard" @endif>
            Store mode: <span class="btn btn-success btn-sm font-size-14 ml-2">@if($owner_array->error != '1')Live @else Error @endif</span>
            </a>
        </div>
    @endif
@else
    <div href="/account/subscriptions" class="bg-dark-4 text-white text-center font-size-16 font-weight-500 w-200 mx-auto card m-0 mb-30">
        <div class="card-body p-5 text-white">
            <a href="/account/subscriptions" class="btn btn-dark btn-sm font-size-14 ml-2">My Subscriptions</a>
        </div>
    </div>
@endif

    <div class="h-250 draw-grad-up">
        <div class="text-center blue-grey-800 m-0 mt-50">
            <a class="avatar avatar-xxl" href="javascript:void(0)">
                <img id="server_icon" src="{{ $twitter_account->profile_image }}" alt="...">
            </a>
            <div class="font-size-50 blue-grey-100 mb--5" id="guild_name">{{ $twitter_account->screen_name }}</div>
            <div class="font-size-16 blue-grey-100 w-400 mx-auto">Description</div>
            <span><button type="button" class="btn btn-sm btn-round btn-dark btn-icon mb-10" id="btn_copy-url" data-toggle="tooltip" data-original-title="Copy Link" data-placement="right"><i class="wb-link"></i></button></span>
        </div>
    </div>
    <div class="container">

        <div class="row">
            <div class="col-xl-2 col-lg-1 col-md-12 order-1 order-lg-2">
                <div class="d-flex justify-content-center pt-40 pt-sm-60">
                    <div class="xx banner-image">
                        <div class="xx-head"></div>
                        <div class="xx-body"></div>
                        <div class="xx-hand"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1 col-md-12 order-2 order-lg-1">

                <div class="row pt-30">
                    <div class="col-md-12">
                        <div class="container">

                            <div class="panel-body p-0">
                                <div class="panel-group" id="accordian_main" aria-multiselectable="true" role="tablist">
                                   
                                    <div class="panel" id="role-{{ $twitter_account->twitter_id }}">
                                        <div class="panel-heading p-20 d-flex flex-row flex-wrap align-items-center justify-content-between" id="heading_{{ $twitter_account->twitter_id }}" role="tab">
                                            <div class="text-center">
                                                <a data-toggle="collapse" href="#tab_{{ $twitter_account->twitter_id }}" data-parent="#accordian_main" aria-expanded="true" aria-controls="tab_{{ $twitter_account->twitter_id }}">
                                                    <div class="badge badge-primary badge-lg font-size-18 text-white" style="background-color: #00ACEE"><i class="icon-twitter mr-2"></i> <span>{{ $twitter_account->screen_name }}</span></div>
                                                </a>
                                            </div>
                                            <div class="w-100 hidden-sm-down">
                                                <button data-url="/slide-product-purchase/{{ $twitter_account->twitter_id }}" data-toggle="slidePanel" type="button"
                                                class="btn btn-sm btn-success float-right">Select <i class="icon wb-arrow-right ml-2" ></i>
                                                </button>
                                            </div>
                                            <div class="w-20 hidden-md-up">
                                                <button class="btn btn-success p-1" data-url="/slide-product-purchase/{{ $twitter_account->twitter_id }}" data-toggle="slidePanel">
                                                    <i class="icon wb-arrow-right" ></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <input readonly type="text" value="https://beastly.store/{{ $twitter_store->url }}" id="input_copy-url" style="opacity:0">
@endsection

@section('scripts')

<script type="text/javascript">
$(function() {
   $('#btn_copy-url').click(function() {
     $('#input_copy-url').focus();
     $('#input_copy-url').select();
     document.execCommand('copy');
     $('#btn_copy-url').attr('data-original-title', 'Copied!').addClass('btn-primary').removeClass('btn-dark');
     $('html .tooltip-inner').text('Copied!')
    setTimeout(function(){
        $('#btn_copy-url').attr('data-original-title', 'Copy Link').addClass('btn-dark').removeClass('btn-primary');
        $('html .tooltip-inner').text('Copy Link')
    }, 1000);
   });
});
</script>

@endsection
