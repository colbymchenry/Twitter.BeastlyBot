module.exports = {startLoopLong, startLoopShort, sendMessage, sendWebNotification, sendWebNotificationFromSub};

// Start the web socket
var index = require('./index.js');
var session_handler = require('./repeating_handlers/SessionHandler.js');
var refund_handler = require('./repeating_handlers/RefundHandler.js');
var dispute_handler = require('./repeating_handlers/DisputeHandler.js');
var payout_handler = require('./repeating_handlers/PayoutHandler.js');
var subscription_handler = require('./repeating_handlers/SubscriptionHandler.js');

// TODO: Increaste to 15 minutes
function delayLong() {
    return new Promise(resolve => setTimeout(resolve, 1000 * 300)); // 5 minute interval
}

// TODO: Increase to 3 minutes
function delayShort() {
    return new Promise(resolve => setTimeout(resolve, 1000 * 10)); // 10 second interval
}

async function startLoopLong() {
    while (true) {
        await delayLong();
        // payout_handler.run();
        dispute_handler.run();
        session_handler.run();
        // end_subscriptions();//
        refund_handler.run();
    }
}

async function startLoopShort() {
    while (true) {
        await delayShort();
        subscription_handler.init_subscriptions(); //// this causes problems if not on live server
        subscription_handler.end_subscriptions(); // TODO: Fix this and move to loop long
    }
}


function sendMessage(msg, priority) {
    if (msg === null || msg === '' || msg === undefined) return;
    index.mysqlConnection.query("SELECT * FROM users WHERE priority='" + priority + "';", function (err, result) {
        if (err) {
            return;
        }

        var i;
        for (i = 0; i < result.length; i++) {
            var user_result = result[i];
            if (user_result.discord_id !== null) {
                index.bot.users.fetch(user_result.discord_id).then(user => {
                    user.send(msg)
                }).catch(error => {
                });
            }
        }
    });
}

function sendWebNotification(user_id, type, message) {
    index.mysqlConnection.query(`INSERT INTO notifications (user_id, type, message) VALUES ('${user_id}', '${type}', '${message}');`, function(err, result) {
        // simple error handling
        if (err) {
            sendMessage(err.message, 5);
            console.log(err);
            return;
        }
    });
}

function sendWebNotificationFromSub(subscription, type, message) {
    now = new Date();
    index.stripe.plans.retrieve(subscription.items.data[0].plan.id,
        function(err, plan) {
            // simple error handling
            if (err) {
                sendMessage(err.message, 3);
                console.log(err);
                return;
            }
            sendWebNotification(plan.metadata.user_id, type, message);
        }
    );
}
