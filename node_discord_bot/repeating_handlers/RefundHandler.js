module.exports = {run, refundOrder};
var index = require('../index.js');
var task_handler = require('../TaskHandler.js');
var subscription_handler = require('./SubscriptionHandler.js');

function run() {

    var decision = "1";
    index.mysqlConnection.query("SELECT * FROM refunds WHERE decision = '" + decision + "';", function(err, refunds, fields) {
        // simple error handling
        if(err) {
            task_handler.sendMessage(err.message, 3);
            return;
        }
        // if there are no new orders return
        if(refunds.length < 1) return;
        // if the order return is already complete

        refunds.forEach(function (refund) {

            if(refund.kick == 1) return;

            var kick = "1";
            // the Stripe subscription ID
            var sub_id = refund.sub_id;


            index.stripe.subscriptions.retrieve(sub_id, function(err, subscription) {
                // asynchronously called
                if(err){
                    //
                }else{

                    if (subscription.metadata['ended'] != 'true'){
                        if(subscription.plan.id == ('plan_GbiiDSRkOovFPF' || 'plan_GbiisRXZmt3IFC')){
                            subscription_handler.endPartnerSubscription(subscription);
                            //console.log("endedPartnerSub")
                        }else{
                            subscription_handler.end_subscription(subscription);
                            //console.log("endedSub")
                        }

                    }
                    index.mysqlConnection.query("UPDATE refunds SET kick = '" + kick + "' WHERE sub_id = '" + sub_id + "';", function (err, result) {
                        if (err) {
                            task_handler.sendMessage(err.message, 3);
                            console.log(err);
                        }
                    })

                }
                }
            )
        })
    })

}

function refundOrder(subscription_id, hide) {
    index.stripe.subscriptions.retrieve(subscription_id, async function(err, subscription) {
        index.stripe.invoices.retrieve(subscription.latest_invoice, function(err, invoice) {
            // simple error handling
            if (err) {
                task_handler.sendMessage(err.message, 3);
                console.log(err);
                return;
            }

            index.stripe.refunds.create( {charge: invoice.charge}, function(err, refund) {
                // simple error handling
                if (err) {
                    task_handler.sendMessage(err.message, 3);
                    console.log(err);
                    return;
                }
               
                if (hide === null || hide === '' || hide === undefined) {
                    task_handler.sendWebNotificationFromSub(subscription, 'warning', `#${invoice.number} was refunded.`, 'bot_error_refund', `${invoice.number}`);
                }
            });
        });
    });
}
