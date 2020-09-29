module.exports = {run};
var index = require('../index.js');
var task_handler = require('../TaskHandler.js');

/**
 * How it works
 *
 * We handle payouts by transfer, payouts are done by Stripe after transfer. We will refer to transfer as payout.
 * We must always update Invoice metadata to: paid_out, reversed, refunded, disputed, fail_country, fail_void_remove_stripe (true/false)
 *
 * [Stripe Invoices List] We get each paid invoice then check if it has a subscription attached (not one-time payment) then if the invoice has an id (for some reason some dont lol)
 * Next we DO NOT continue if
    1) Invoice has been refunded
    2) Invoice has been reversed (the invoice had already been paid out then reversed back [for a dispute])
    3) If the invoice has been paid out already
    4) If the invoice has already been attempted to payout but their country is not US
 * We get:
    - Owners Stripe account id to payout from invoice, if not fail
    - Owners application fee from the invoice, if not we default to 5%
    - Owners payout delay from invoice, if not default to 7 days
    - The date and the invoice stripe payout delay date, compare them
 * The magic begins when date now > invoice payment date + payout delay
    We then double check if the invoice metadata has length, OR if the invoice paid out is false AND if the invoice has been disputed id false AND if the invoice country is not fail (but if voided continue)
 * [Stripe Accounts Retrieve] Next we get their Stripe account
    - We check if their Stripe account is from the US
        - [Stripe Transfers Create] We create the transfer for the payout amount and UPDATE metadata (with info for future reference)
        - [Stripe Invoices Update] If that is successful we UPDATE the invoice metadata to paid_out=true (and info for future reference)
    - If the invoice stripe account id country is not US
        - We must setup the error (1) to close store and send notice to connect new US Stripe account
        - Check then stop if we have done this before and have manually voided it with fail_void_remove_stripe=true for some reason (example to payout the invoice with a manually edited new stripe_express_id in invoice metadata)
            - [Stripe Invoices Update] Update the invoice to fail_country=true, and paid_out=false (also take note of other info)
            - We can set their user db country to CA, however this does not affect anything (just for future reference, or if we remove error column)
 * Done.
 *
 * **/

function run(){

    // We get all Paid Stripe invoices
    index.stripe.invoices.list(
        {status: 'paid'},
        function (err, invoices) {
            if(err) return;
            invoices.data.forEach(function (invoice) {
               // We get each invoice then check if it has a subscription attached (not one-time payments etc.) then if the invoice has an id (for some reason some dont lol)
                if (invoice.subscription != null && invoice.id != null){
                    var invoice_id = invoice.id;
                    // Next we DO NOT continue if
                    // 1) Invoice has been refunded
                    // 2) Invoice has been reversed (the invoice had already been paid out then reversed back)
                    // 3) If the invoice has been paid out already
                    // 4) If the invoice has already been attempted to payout but their country is not US
                    if(invoice.metadata['refunded'] == 'true') return;
                    if(invoice.metadata['reversed'] == 'true') return;
                    if(invoice.metadata['paid_out'] == 'true') return;
                    if(invoice.metadata['fail_country'] == 'true') return;

                    // Owners Stripe account id to payout from invoice, if not we return
                    if(invoice.lines.data[0].plan.metadata['stripe_express_id']){
                        var stripe_express_id = invoice.lines.data[0].plan.metadata['stripe_express_id'];
                    }
                    if(stripe_express_id == null) return;

                    // Owners application fee from the invoice, if not we default to 5%
                    if (invoice.lines.data[0].plan.metadata['app_fee_percent']){
                        var app_fee_percent = invoice.lines.data[0].plan.metadata['app_fee_percent'];
                    }else{
                        var app_fee_percent = 5;
                    }
                    // Owners payout delay from invoice, if not default is 7 days
                    if (invoice.lines.data[0].plan.metadata['payout_delay']){
                        var stripe_delay_days = invoice.lines.data[0].plan.metadata['payout_delay'];
                    }else{
                        var stripe_delay_days = 7;
                    }

                    // get the date
                    var paid_at = new Date(invoice.status_transitions.paid_at * 1000);
                    var now = new Date();
                    // get the date plus the Invoice stripe payout delay
                    paid_at.setDate(paid_at.getDate() + stripe_delay_days);
                    // Testing: We can use this to payout right away all invoices
                    //paid_at.setDate(paid_at.getDate() - 1);

                    // Check if the payout is past the delay days
                    if (now > paid_at) {

                        var updated = false;
                        // We then double check if the invoice metadata has length, OR if the invoice paid out is false AND if the invoice has been disputed (dont payout) AND if the invoice country is not fail
                        if (Object.keys(invoice.metadata).length < 1 || invoice.metadata['paid_out'] == 'false' && invoice.metadata['disputed'] != 'true' && (invoice.metadata['fail_country'] != 'true' || invoice.metadata['fail_void_remove_stripe'] == 'true')) {
                            // Here we do math for payout amount
                            var amount_paid_out = invoice.amount_paid * ((100 - app_fee_percent)/100);

                            // Next we get their Stripe account
                            index.stripe.accounts.retrieve(
                                stripe_express_id,
                                function(err, account) {
                                  if(err){
                                    task_handler.sendMessage(err.message, 3);
                                    console.log(err);
                                // We check if their Stripe account is from the US
                                  }else if (account.country == 'US') {
                                    // We create the transfer for the payout amount and UPDATE meta data (with other info for future reference)
                                    index.stripe.transfers.create({
                                        amount: amount_paid_out,
                                        currency: "usd",
                                        destination: stripe_express_id,
                                        metadata:{transfer_inv: invoice_id, destination: stripe_express_id, amount: amount_paid_out, fee: app_fee_percent},
                                    }, function (err, transfer) {
                                        if (err) {
                                            task_handler.sendMessage(err.message, 3);
                                            console.log(err);
                                        }else{
                                            // If that is successful we UPDATE the invoice metadata to Paid out (and other info for future reference)
                                            index.stripe.invoices.update(invoice.id,
                                                {metadata: {paid_out: true, paid_amount: amount_paid_out, fee: app_fee_percent}},
                                                function (err, invoice) {
                                                    if (err) {
                                                        task_handler.sendMessage(err.message, 3);
                                                        console.log(err);
                                                    }
                                                }
                                            );

                                        }
                                    }); //end transfer create

                                  }else{
                                // If the invoice stripe account id country is not US
                                    console.log('Could not send transfer ' + stripe_express_id + ', ' + invoice_id + ' (not in US).');

                                    // We must setup the error to stop all future payments and send notice to connect US Stripe account
                                    // Check if we have done this then voided it for some reason (example to payout the invoice with a manually edited new stripe account id in metadata)
                                    if(invoice.metadata['fail_void_remove_stripe'] != 'true'){
                                        index.stripe.invoices.update(invoice.id,
                                            // Update the invoice to fail_country, and paid_out false (also take note of other info)
                                            {metadata: {paid_out: false, paid_amount: amount_paid_out, fee: app_fee_percent, fail_country: true}},
                                            function (err, invoice) {
                                                if (err) {
                                                    task_handler.sendMessage(err.message, 3);
                                                    console.log(err);
                                                }else{
                                                    // Set the owners account to Error 1 (not US)
                                                    index.mysqlConnection.query("UPDATE users SET error = '1' WHERE stripe_express_id = '" + stripe_express_id + "';", function (err, result) {
                                                        if (err) {
                                                            task_handler.sendMessage(err.message, 3);
                                                            console.log(err);
                                                        }else{
                                                            // Update the invoice to add fail_void_remove_stripe to false for future use
                                                            index.stripe.invoices.update(invoice.id,
                                                                {metadata: {fail_void_remove_stripe: false}},
                                                                function (err, invoice) {
                                                                    if (err) {
                                                                        task_handler.sendMessage(err.message, 3);
                                                                        console.log(err);
                                                                    }
                                                                }
                                                            )
                                                            // We can set their country to CA, however this does not affect anything (just for future reference, data keeping)
                                                            index.mysqlConnection.query("UPDATE users SET country = 'CA' WHERE stripe_express_id = '" + stripe_express_id + "';", function (err, result) {
                                                                if (err) {
                                                                    task_handler.sendMessage(err.message, 3);
                                                                    console.log(err);
                                                                }
                                                                // Kept this code incase we use the CA field instead of Error 1
                                                                /*else{
                                                                    index.stripe.invoices.update(invoice.id,
                                                                        {metadata: {fail_void_remove_stripe: false}},
                                                                        function (err, invoice) {
                                                                            if (err) {
                                                                                sendMessage(err.message, 3);
                                                                                console.log(err);
                                                                            }
                                                                        }
                                                                    );

                                                                }*/
                                                            })

                                                        };
                                                    })

                                                }
                                            }
                                        );

                                    }

                                  } // end function
                                }
                              ); // end accounts retrieve

                        }
                    }
                }})
            })
        };
