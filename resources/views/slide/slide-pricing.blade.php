
<header class="slidePanel-header bg-blue-500">
    <div class="slidePanel-actions" aria-label="actions" role="group">
        <button type="button" class="btn btn-icon btn-pure btn-inverse slidePanel-close actions-top icon wb-close"
                aria-hidden="true" id="back-btn"></button>
    </div>
    <h1>Pricing</h1>
</header>

    <div class="page-header my-10">
        @if(auth()->user()->StripeConnect()()->express_id == null)
        <div class="page-header-actions add-pulse">
            <a class="btn btn-primary btn-round"
               href="{{ 'https://connect.stripe.com/express/oauth/authorize?redirect_uri=' . env('APP_URL') . '&client_id=' . env('STRIPE_CLIENT_ID')  }}">
                Connect Stripe
                <i class="icon-stripe ml-2" aria-hidden="true"></i>
            </a>
        </div>
        @endif
    </div>

    <div class="page-content-table app-beast">
        <div class="page-main">
            <table class="table" data-plugin="animateList" data-animate="fade" data-child="tr">
                <tbody id="servers-table">
                    
                </tbody>
            </table>
        </div>
    </div>
 
@include('partials/clear_script')
