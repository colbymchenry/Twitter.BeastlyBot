module.exports = {run};
var index = require('../index.js');
var task_handler = require('../TaskHandler.js');
var subscription_handler = require('./SubscriptionHandler.js');

/**
 * How it works
 *
 * We handle disputes by searching all disputes then update invoice metadata to disputed=true, and
   if paid_out=true we reverse and add reversed=true, charge_reversed=true (to invoice)
 *
 * How we deal with it:
 *      We send back all info we have to bank as evidence (add evidence_submitted=true). This will take long time to hear back.
 *
 * Punishment:
 *      We send a $15 invoice (no profit, Stripe sends us this too) then add dispute_fee_issued=true to invoice metadata
 *      We add 7 days to the Owners stripe payout delay at same time
 *
 * If WON we set metadata back to disputed=false, and if was paid_out we send transfer again adding
   dispute_unreversed=true to invoice metadata and charge_unreversed=true to dispute meatadata
 *
 * If LOST we cancel subscription and end subscription (TODO: cancel future invoices)
 *
 * **/

function run(){

    // get stripe disputes (all)
    index.stripe.disputes.list(
        {},
        function(err, disputes) {
            if(err) return;
            // loop all disputes
            disputes.data.forEach(function (dispute) {
               /* var dispute = dispute;
                console.log(invoice.metadata, invoice.amount_paid); */

               // get data from each dispute
                var dispute_ch = dispute.charge;
                var dispute_id = dispute.id;
                var dispute_cus = dispute.customer;
                // make global var for all functions
                var Global = {};
                var transfer_id = Global.otransfer_id;
                var transfer_amount = Global.otransfer_amount;

                // retreive the charge associated with dispute
                  index.stripe.charges.retrieve(
                    dispute_ch,
                    function(err, charge) {
                    if (err) {
                        task_handler.sendMessage(err.message, 3);
                        console.log(err);
                    }else if (charge != null) {
                      var dispute_in = charge.invoice;
                      //var dispute_cus = charge.customer;
                      var dispute_ch_name = charge.billing_details.name;
                      var dispute_ch_postal = charge.billing_details.address.postal_code;
                        // retrieve the  invoice associated with the charge
                      index.stripe.invoices.retrieve(dispute_in, function(err, invoice) {
                        if(err || invoice == null) {
                            console.log(err);
                            return;
                        }

                          var dispute_invoice_id = invoice.id;
                          var dispute_inv_customer = invoice.customer;
                          var dispute_inv_created = invoice.lines.data[0].plan.created;
                          var dispute_inv_desc = invoice.lines.data[0].description;
                          var dispute_inv_sub = invoice.subscription;
                          var dispute_inv_customer_email = invoice.customer_email;
                          var dispute_inv_stripe_express_id = invoice.lines.data[0].plan.metadata['stripe_express_id'];

                          // if dispute is not replied too with evidence

                            if((dispute.status == "warning_needs_response" || dispute.status == "needs_response") && dispute.metadata['evidence_submitted'] != 'true' ){

                            // update the dispute with evidence, and submit
                                index.stripe.disputes.update(
                                    dispute_id, {
                                        metadata: {
                                            evidence_submitted: true
                                        },
                                        evidence: {
                                            billing_address: dispute_ch_postal, customer_name: dispute_ch_name, customer_email_address: dispute_inv_customer_email, product_description: dispute_inv_desc
                                        },
                                        submit: true
                                    },
                                    function(err, dispute) {
                                        //console.log(dispute)
                                        if (err) {
                                            task_handler.sendMessage(err.message, 3);
                                            console.log(err);
                                        }else{
                                            // update the invoice that its been disputed = true
                                            index.stripe.invoices.update(dispute_invoice_id,
                                                {metadata: {disputed: true}},
                                                function (err, invoice) {
                                                    if (err) {
                                                        task_handler.sendMessage(err.message, 3);
                                                        console.log(err);
                                                    }
                                                }
                                            );
                                        }
                                        // get the stripe delay days for server owner and add 7 to it
                                        index.mysqlConnection.query("SELECT stripe_delay_days FROM users WHERE stripe_express_id = '" + dispute_inv_stripe_express_id + "';", function (err, result) {

                                                if (err) {
                                                    task_handler.sendMessage(err.message, 3);
                                                    console.log(err);
                                                    return;
                                                }

                                                if(result.length > 0){
                                                    var stripe_delay = result[0].stripe_delay_days;
                                                }else{
                                                    var stripe_delay = 7;
                                                }

                                                var add_delay = 7;
                                                var new_delay_days = +stripe_delay + +add_delay;


                                                index.mysqlConnection.query("UPDATE users SET stripe_delay_days = '" + new_delay_days + "' WHERE stripe_express_id = '" + dispute_inv_stripe_express_id + "';", function (err, result) {
                                                    if (err) {
                                                        task_handler.sendMessage(err.message, 3);
                                                        console.log(err);
                                                    }
                                                })

                                        })
                                    }
                                );

                            // if dispute has been replied too (evidence_submitted=true)
                            }else{// end send response

                                // check if its won, and if its been paid out before, if that payout was reversed bc of the charge and not unreversed yet
                                if(dispute.status == "won" && invoice.metadata['paid_out'] == 'true' && invoice.metadata['charge_reversed'] == 'true' && invoice.metadata['dispute_unreversed'] != 'true' && dispute.metadata['charge_unreversed'] != 'true'){
                                    // send back money from stripe reversal

                                    // get all transfers
                                    index.stripe.transfers.list(
                                        {destination: dispute_inv_stripe_express_id},
                                        function(err, transfers) {
                                            if(err) return;
                                            transfers.data.forEach(function (transfer) {
                                               // transfer_array = transfer.toArray()
                                                if(transfer.metadata['transfer_inv'] == dispute_in){


                                                    var transfer_amount = transfer.amount;
                                                    // create a new transfer with same amount * 0.8
                                                    index.stripe.transfers.create({
                                                        amount: (transfer_amount * 0.80),
                                                        currency: "usd",
                                                        destination: dispute_inv_stripe_express_id,
                                                        metadata:{transfer_inv: dispute_invoice_id, destination: dispute_inv_stripe_express_id, amount: transfer_amount},
                                                    }, function (err, transfer) {
                                                        if (err) {
                                                            task_handler.sendMessage(err.message, 3);
                                                            console.log(err);
                                                        }else{
                                                            // update the invoice to unreversed
                                                            index.stripe.invoices.update(invoice.id,
                                                                {metadata: {dispute_unreversed: true}},
                                                                function (err, invoice) {
                                                                    if (err) {
                                                                        task_handler.sendMessage(err.message, 3);
                                                                        console.log(err);
                                                                    }
                                                                }
                                                            );
                                                            // update the dispute to unreversed
                                                            index.stripe.disputes.update(
                                                                dispute_id,
                                                                {metadata: {charge_unreversed: true}},
                                                                function(err, dispute) {
                                                                // asynchronously called
                                                                }
                                                            );
                                                        }
                                                    });
                                                }
                                            });
                                        });
                                }
                                // if won and disputed it still true, we can make disputed = false (so it will be paid out if not already)
                                if(dispute.status == "won" && invoice.metadata['disputed'] == 'true'){
                                    index.stripe.invoices.update(invoice.id,
                                        {metadata: {disputed: false}},
                                        function (err, invoice) {
                                            if (err) {
                                                task_handler.sendMessage(err.message, 3);
                                                console.log(err);
                                            }
                                        }
                                    );
                                }

                                // if dispute has been lost or charge refunded we must end the subscription
                                if((dispute.status == "lost" || dispute.status == "charge_refunded") && invoice.metadata['subscription_ended'] != 'true') {

                                    // update the invoice to subscription_ended = true
                                    index.stripe.invoices.update(invoice.id,
                                        {metadata: {subscription_ended: true}},
                                        function (err, invoice) {
                                            if (err) {
                                                task_handler.sendMessage(err.message, 3);
                                                console.log(err);
                                            }
                                        }
                                    );

                                    subscription_handler.cancelSubscription(dispute_inv_sub);
                                    subscription_handler.end_subscription(dispute_inv_sub);

                                }
                        }

                        // if an invoice has been disputed and charge has not been reversed we do that here with createReversal
                        if(invoice.metadata['disputed'] == 'true' && invoice.metadata['reversed'] == null){

                            index.stripe.transfers.list(
                                {destination: dispute_inv_stripe_express_id},
                                function(err, transfers) {
                                    if(err) return;
                                    transfers.data.forEach(function (transfer) {
                                       // transfer_array = transfer.toArray()
                                        if(transfer.metadata['transfer_inv'] == dispute_in){
                                            transfer_id = transfer.id;
                                            Global.otransfer_amount = transfer.amount;
                                               // if(transfer.reversed != true){
                                                index.stripe.transfers.createReversal(
                                                    transfer_id,
                                                    { },
                                                    function(err, reversal) {
                                                    // asynchronously called
                                                    if (err) {
                                                        task_handler.sendMessage(err.message, 3);
                                                        console.log(err);
                                                    }else{
                                                        // update the invoice to reversed and charge_reversed = true
                                                        index.stripe.invoices.update(
                                                            dispute_in,
                                                            {metadata: {reversed: true, charge_reversed: true}},
                                                            function(err, invoice) {
                                                            // asynchronously called
                                                            }
                                                        );
                                                        // update the dispute to charge_reversed = true
                                                        index.stripe.disputes.update(
                                                            dispute_id,
                                                            {metadata: {charge_reversed: true}},
                                                            function(err, dispute) {
                                                            // asynchronously called
                                                            }
                                                        );

                                                    }
                                                    }
                                                )//}
                                        }

                                    })


                                }
                            )

                            // TODO: cancel all upcoming invoices if disputed



/*
                             index.stripe.invoices.retrieveUpcoming(
                                {customer: dispute_cus, subscription_cancel_now: true},
                                function(err, upcoming) {
                                    if (err) {
                                        sendMessage(err.message, 3);
                                        console.log(err);
                                    }
                                }
                            );


                           // delete the subscription

                           index.stripe.subscriptions.del(
                                dispute_inv_sub,
                                function(err, confirmation) {
                                    if (err) {
                                        sendMessage(err.message, 3);
                                        console.log(err);
                                    }
                                }
                            );  */

                            // if disputed we must issue the $15 fee that Stripe sends us
                            if(invoice.metadata['disputed'] == 'true' && invoice.metadata['dispute_fee_issued'] != 'true'){
                                index.mysqlConnection.query("SELECT stripe_customer_id FROM users WHERE stripe_express_id = '" + dispute_inv_stripe_express_id + "';", function (err, result_stripe_customer_id) {
                                    if (err) {
                                        task_handler.sendMessage(err.message, 3);
                                        console.log(err);
                                        return;
                                    }

                                    if (result_stripe_customer_id[0].stripe_customer_id < 1) return;

                                    // create the invoice
                                    index.stripe.invoiceItems.create({
                                        customer: result_stripe_customer_id[0].stripe_customer_id,
                                        amount: 1500,
                                        currency: 'usd',
                                        description: 'Stripe one-time dispute fee.',
                                    }, function(err, invoiceItem) {
                                        // asynchronously called
                                    // send the invoice
                                        index.stripe.invoices.create({
                                        customer: result_stripe_customer_id[0].stripe_customer_id,
                                        auto_advance: true, // auto-finalize this draft after ~1 hour
                                        }, function(err, invoice) {
                                        // asynchronously called
                                        });
                                    });
                                    // update the original invoice to dispute_fee_issed=true
                                    index.stripe.invoices.update(
                                        dispute_in,
                                        {metadata: {dispute_fee_issued: true}},
                                        function(err, invoice) {
                                        // asynchronously called
                                        }
                                    );



                                });
                            }


                            }  // end reverse charge


                        }) //end invoice
                    }
            }
        )}
    )}
)};
