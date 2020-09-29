module.exports = {run};

var index = require('../index.js');
var task_handler = require('../TaskHandler.js');

function run() {
    // TODO: Not working
    // index.mysqlConnection.query("SELECT * FROM sessions WHERE valid_until <= NOW();", function (err, result) {
    //     if (err) {
    //         task_handler.sendMessage(err.message, 3);
    //         console.log(err);
    //         return;
    //     }

    //     if (result.length < 1) return;

    //     for (var i = 0; i < result.length; i++) {
    //         var r = result[i];
    //         index.stripe.checkout.sessions.retrieve(r.id, function (err, session) {
    //                 if (session.subscription === null) {
    //                     index.stripe.customers.update(session.customer,
    //                         {coupon: ''},
    //                         function (err, customer) {
    //                             if (err) {
    //                                 task_handler.sendMessage(err.message, 3);
    //                                 console.log(err);
    //                             }
    //                             index.mysqlConnection.query("DELETE FROM sessions WHERE id='" + session.id + "'", function (err, result) {
    //                                 if (err) {
    //                                     task_handler.sendMessage(err.message, 3);
    //                                     console.log(err);
    //                                 }
    //                             });
    //                         }
    //                     );
    //                 }
    //             }
    //         );
    //     }
    // });
}
