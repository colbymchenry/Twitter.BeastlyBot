@extends('layouts.site')

@section('title', 'Welcome')
@section('metadata')
<meta name="description" content="The beastly subscription bot for Twitter, monetize followers & earn from your feed.">
<meta name="keywords" content="BeastlyBot, Beastly Bot, Twitter, Subscription, Payments, Feed, Private">
<meta name="author" content="BeastlyBot">
@endsection
@section('content')

    <!--banner starts-->
    <div class="banner-area bg-4 pt-200 pt-sm-150 pb-0">
        <div class="container">
            <!--<div class="row align-items-center">-->
            <div class="row">
                <div class="col-lg-6 col-md-6 order-2 order-md-1">
                    <div class="d-flex justify-content-center pt-sm-60">
                        <div class="xx banner-image">
                            <div class="xx-head"></div>
                            <div class="xx-body"></div>
                            <div class="xx-hand"></div>
                        </div>
                    </div>
                <!--
						<div class="banner-image wow fadeIn">
							<img src="{{ asset('site/assets/images/3.png') }}" alt="" />
						</div>-->
                </div>
                <div class="col-lg-6 col-md-6 order-1 order-md-2">
                    <!--<div class="xx float-right">
                        <div class="xx-head"></div>
                        <div class="xx-body"></div>
                        <div class="xx-hand"></div>
                    </div>-->
                    <div class="banner-caption pt-50 pt-sm-none">
                        <!--<div class="wow fadeInUp" data-wow-delay=".3s">
                            <h1><span>Discord</span></h1>
                        </div>-->
                        <div class="wow fadeInUp" data-wow-delay=".1s" style="visibility:hidden">
                            <h1 class="mb-10"><span class="text-white">Beastly Bot</span></h1>
                        </div>
                        <div class="wow fadeInUp" data-wow-delay=".2s" style="visibility:hidden">
                            <p class="mb-20">The beastly subscription bot for Twitter,<br/> monetize followers & earn from your feed.</p>
                        </div>
                        <div class="banner-action wow fadeInUp" data-wow-delay=".3s" style="visibility:hidden">
                            @auth
                                <a href="{{ env('APP_URL') }}/dashboard" class="btn-common radius-50 btn-pink mr-20">Dashboard</a>
                            @else
                                <a href="{{ env('APP_URL') }}/dashboard" class="btn-common radius-50 btn-pink mr-20">Create Shop</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('site/blocks/steps')

    </div>
    <!--banner ends-->

    <div class="cta-area mt-40 mt-sm-55">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="cta-inner bg-fb5c71 br-radius-10">
                        <div class="row height-180 align-items-center">
                            <div class="col-lg-9">
                                <div class="cta-text">
                                    <h3>Start your Twitter Shop!</h3>
                                    <p>More Traffic, More Exposure, More Followers, with Beastly Bot</p>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="cta-btn text-right">
                                    @auth
                                        <a href="/dashboard"
                                           class="btn-common mt-sm-25">Go to dashboard</a>
                                    @else
                                        <a href="{{ route('twitter.login') }}"
                                           class="btn-common mt-sm-25">Login with Twitter</a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--about starts-->
    <div class="about-area pt-95 pt-sm-77 pb-50">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <div class="section-title mb-40">
                        <h2><span>Take subscription payments</span></h2><br/>
                        <h2><span>from Twitter</span></h2>
                        <p class="mt-35">Beastly Bot is the best subscription bot for Twitter, fully managing
                            subscription payments for followers and your feed. </p>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="service-single style-2 wow fadeInUp" data-wow-delay=".3s">
                                <i class="icon-twitter"></i>
                                <div class="service-single-brief">
                                    <h4><span>Mr. Beastly</span></h4>
                                    <p>The Beastly Bot is smart. It does it all. Running on your own custom shop URL beastly.store!</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="service-single style-2 wow fadeInUp" data-wow-delay=".4s">
                                <i class="icon-gift"></i>
                                <div class="service-single-brief">
                                    <h4><span>Daily Payout</span></h4>
                                    <p>You can track earnings live, with payments sent to your Stripe continuously throughout the day.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <a href="/about" class="btn-common mt-15 btn-pink radius-50 wow fadeInUp" data-wow-delay=".5s">About
                                Us</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 offset-lg-1">
                    <div class="ds-sm-none wow zoomIn">
                        <div class="pricing-table-single wow fadeIn" data-wow-delay=".3s">
                            <div class="pricing-table-head">
                                <!--<h3>Your Store is</h3>-->
                                <p>5% Transaction Fee</p>
                                <h4><sup>$</sup>0<span> / first month</span></h4>
                                <!--<h4 class="pt-20">FREE</h4>-->
                            </div>
                            <div class="pricing-table-body">
                                <ul class="list-sign">
                                    <li>Personalized Store Front</li>
                                    <li>Unlimited Subscriptions</li>
                                    <li>Daily Reports & Payout</li>
                                    <li>Automatic User Management</li>
                                    <li>Help & Support</li>
                                </ul>
                                @auth
                                <a href="/dashboard" class="btn-common">Go to Dashboard</a>
                                @else
                                <a href="https://discord.beastlybot.com/dashboard" class="btn-common">Get Started</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                    <!--<img src="site/assets/images/about/3.png" alt="" />-->
                </div>
            </div>
        </div>
    </div>
    <!--about ends-->



    <!--services starts-->
    <div class="services-area bg-grey-2 pt-50 pt-sm-47 pb-50 pb-sm-50">
        <div class="container z-index">
            <div class="row align-items-center">
                <div class="col-lg-4 col-sm-12">
                    <div class="section-title">
                        <h2><span>Let’s Check</span></h2>
                        <div>
                            <h2><span>Our Features</span></h2>
                        </div>
                    </div>
                    <div class="service-single style-3 style-5 mt-85 mt-sm-45 wow zoomIn" data-wow-delay=".3s">
                        <i class="icon-discord"></i>
                        <div class="service-single-brief">
                            <h4><span>Beastly Bot</span></h4>
                            <p>The Beastly Bot is smart. Managing your servers subscribers, adding a role and
                            removing when the subscription is over.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <!--<div class="service-single style-3 style-5 mt-50 mt-sm-0 wow zoomIn" data-wow-delay=".4s">
                        <i class="ti-announcement"></i>
                        <div class="service-single-brief">
                            <h4><span>Affiliates</span></h4>
                            <p>Have your server members refer others to join a subscription based role, encouraged with
                                a referal payout for each successful subscription.
                            </p>
                        </div>
                    </div>-->
                    <div class="service-single style-3 style-5 mt-50 mt-sm-0 wow zoomIn" data-wow-delay=".4s">
                        <i class="ti-announcement"></i>
                        <div class="service-single-brief">
                            <h4><span>Fraud Protection</span></h4>
                            <p>Beastly Bot will monitor each payment for chargebacks and disputes to prevent fraud, and file proof with bank accordingly.
                            </p>
                        </div>
                    </div>
                    <div class="service-single style-3 style-5 mt-30 wow zoomIn" data-wow-delay=".5s">
                        <i class="icon-gift1"></i>
                        <div class="service-single-brief">
                            <h4><span>Promotions</span></h4>
                            <p>Add coupons to specific roles, or do a store wide sale. Pick your coupon code, the discount amount,
                                timeline and expiry. You're in control!
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="service-single style-3 style-5 wow zoomIn" data-wow-delay=".6s">
                        <i class="ti-wallet"></i>
                        <div class="service-single-brief">
                            <h4><span>Daily Payouts</span></h4>
                            <p>Payments go directly through Stripe to your account, you'll receive continuous payments throughout the day.</p>
                        </div>
                    </div>
                    <div class="service-single style-3 style-5 mt-30 wow zoomIn" data-wow-delay=".7s">
                        <i class="ti-pie-chart"></i>
                        <div class="service-single-brief">
                            <h4><span>Analytics</span></h4>
                            <p>Our website displays useful information on all aspects of your server and shop, sales, promotions,
                                and affiliate tracking.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--services ends-->


    <!--testimonial and records start--><!--
		<div class="testimonial-and-records pt-70 pt-sm-78 pb-100 pb-sm-75">
			<div class="container">
				<div class="row">
					<div class="col-lg-6">
						<div class="section-title mb-20">
							<h2><span>What Partners Say?</span></h2>
						</div>
						<div class="testimonial-carousel-2">
							<div class="testimonial-single style-2">
								<div class="testimonial-info">
									<div class="testimonial-thumb">
										<img src="https://cdn.discordapp.com/avatars/301838193018273793/90ae4012595efe8c05b66649d52f4859.png?size=2048" alt="" />
									</div>
									<div class="testimonial-name">
										<h5>Robert</h5>
										<span>Robs Server</span>
									</div>
								</div>
								<div class="testimonial-desc">
									<p>"Its pretty nice having the bot do all the work for me, Beastly Bot is truly beastly.”</p>
								</div>
							</div>
							<div class="testimonial-single style-2">
								<div class="testimonial-info">
									<div class="testimonial-thumb">
										<img src="https://cdn.discordapp.com/avatars/301838193018273793/90ae4012595efe8c05b66649d52f4859.png?size=2048" alt="" />
									</div>
                                    <div class="testimonial-name">
										<h5>Robert</h5>
										<span>Robs Server</span>
									</div>
								</div>
								<div class="testimonial-desc">
									<p>"Its pretty nice having the bot do all the work for me, Beastly Bot is truly beastly.”</p>
								</div>
							</div>
							<div class="testimonial-single style-2">
								<div class="testimonial-info">
									<div class="testimonial-thumb">
										<img src="https://cdn.discordapp.com/avatars/301838193018273793/90ae4012595efe8c05b66649d52f4859.png?size=2048" alt="" />
									</div>
									<div class="testimonial-name">
										<h5>Robert</h5>
										<span>Robs Server</span>
									</div>
								</div>
								<div class="testimonial-desc">
									<p>"Its pretty nice having the bot do all the work for me, Beastly Bot is truly beastly.”</p>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="ds-sm-none wow fadeIn" data-wow-delay=".3s">
							<img src="site/assets/images/1.png" alt="" />
						</div>
					</div>
				</div>
			</div>
		</div>-->
    <!--testimonial and records end-->


{{-- @if(count($blogs) > 0)
    <!--blog starts-->
    <div class="blog-area pt-65 pt-sm-47 pb-90 pb-sm-70 bg-grey-1">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="section-title text-center">
                        <h2><span>Our Blogs</span></h2>
                    </div>
                </div>
            </div>
            <div class="row blog-carousel-2 mt-47">
            @foreach($blogs as $blog)
                <div class="col-lg-12">
                    <div class="row blog-single style-3 align-items-center">
                        <div class="col-lg-6">
                            <div class="blog-thumb">
                                <a href="/blog/post/{{ $blog->url_title }}"><img src="{!! $blog->getThumbnail() !!}" alt="{{ $blog->url_title }}"/></a>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="blog-desc">
                            @if(!empty($blog->tags))
                                <ul class="blog-category">
                                    <li><a href="/blog/post/{{ $blog->url_title }}">{{ ucwords(str_replace(',', ' ', $blog->tags)) }}</a></li>
                                </ul>
                            @endif
                                <h3><a href="/blog/post/{{ $blog->url_title }}">{{ $blog->title }}</a></h3>
                                <ul class="blog-date-time">
                                    <li><a href="/blog/post/{{ $blog->url_title }}"><i class="icon-clock"></i> 18th Oct, 2019</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>
    </div>
    <!--blog ends-->
@endif --}}

    @include('site/blocks/cta-bottom')

    <!--subscribe form starts-->
    <!--<div class="subscribe-area bg-f3f8f8">
        <div class="container">
            <div class="row height-160 align-items-center">
                <div class="col-lg-7">
                    <div class="subscribe-text">
                        <h3>Subscribe Newsletter</h3>
                        <p>Subscribe to our email newsletter for useful tips and valuable resources.</p>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="subscribe-form style-3">
                        <input type="text" placeholder="Enter your email" />
                        <button>Subscribe</button>
                    </div>
                </div>
            </div>
        </div>
    </div>-->
    <!--subscribe form ends-->


@endsection
