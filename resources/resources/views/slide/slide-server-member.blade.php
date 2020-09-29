<header class="slidePanel-header">
    <div class="slidePanel-actions" aria-label="actions" role="group">
        <button type="button" class="btn btn-icon btn-pure btn-inverse slidePanel-close actions-top icon wb-close"
                aria-hidden="true"></button>
    </div>
    <h1>{{ $discord_helper->getUsername() }}</h1>
</header>

<!-- nav-tabs -->
<ul class="site-sidebar-nav nav nav-tabs nav-tabs-line" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#sidebar-user" role="tab">
            <i class="icon wb-more-vertical" aria-hidden="true"></i>
            <h5>Roles</h5>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sidebar-payments" role="tab">
            <i class="icon wb-order" aria-hidden="true"></i>
            <h5>Payments</h5>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#sidebar-details" role="tab">
            <i class="icon wb-user-circle" aria-hidden="true"></i>
            <h5>Info</h5>
        </a>
    </li>
</ul>

<div class="site-sidebar-tab-content put-short tab-content">
    <div class="tab-pane fade active show" id="sidebar-user">
        <div>
            <table id="rolesTable" class="table table-hover" data-plugin="animateList" data-animate="fade" data-child="tr">
                <tbody>
                    <thead>
                    <tr>
                        <th class="cell-400 text-left">Role</th>
                        <th class="cell-200">Expires</th>
                    </tr>
                    </thead>
                    @foreach(\App\Subscription::where('user_id', $user_id)->where('store_id', $discord_store->id)->where('active', 1)->get() as $sub)
                        @php
                        $role = $discord_helper->getRole($discord_store->guild_id, $sub->metadata['role_id']);
                        @endphp

                        <tr>
                            <td >
                                <h3><span class="badge m-5" style="color: white;background-color: #{{ dechex($role->color) }};">{{ $role->name }}</span></h3>
                            </td>
                            <td class="text-center">
                                <div class="time">{{ date_format($sub->current_period_end, "m-d-Y") }}</div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="tab-pane fade" id="sidebar-payments">
        <div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="cell-150 text-left">Invoice</th>
                        <th class="cell-100 responsive-hide text-left">Date</th>
                        <th class="cell-100 text-left">Amount</th>
                        <th class="cell-100 text-left">Role</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($invoices as $subscription_id => $invoice_array)
                    @php
                        $subscription = \App\Subscription::where('id', $subscription_id)->first();
                        $role = $discord_helper->getRole($discord_store->guild_id, $subscription->metadata['role_id']);
                    @endphp
                        @foreach($invoice_array as $invoice)
                        <tr data-url="/slide-invoice?id={{ $invoice->id }}&user_id={{ $user_id }}&role_id={{ $role->id }}&guild_id={{ $discord_store->guild_id }}" data-toggle="slidePanel">
                            <td><a href="javascript:void(0)">#{{ $invoice->number }}</a></td>
                            <td class="responsive-hide">
                                <span class="text-muted"><i class="wb wb-time"></i> {{ date("Y-m-d", $invoice->created) }}</span>
                            </td>
                            <td>${{ number_format($invoice->amount_paid/100, 2, '.', ',') }}</td>
                            <td><span class="badge m-5" style="color: white;background-color: #{{ dechex($role->color) }};">{{ $role->name }}</span></td>
                        </tr>
                        @endforeach
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane fade" id="sidebar-details">
        <div>
            <div>
                <div class="list-group-item d-flex flex-row flex-wrap align-items-center justify-content-between">
                    <h5>Email</h5>
                    <div>
                        <input type="text" class="form-control" value="{{ (new \App\StripeHelper(\App\User::where('id', $user_id)->first()))->getStripeEmail() }}" disabled>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('partials/clear_script')
