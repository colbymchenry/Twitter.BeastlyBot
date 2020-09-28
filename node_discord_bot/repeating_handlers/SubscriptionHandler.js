module.exports = {init_subscriptions, end_subscriptions};
var index = require('../index.js');
var task_handler = require('../TaskHandler.js');
var refund_handler = require('./RefundHandler.js');


async function init_subscriptions() {
    /**
     * Any new orders are stored in the "new_subscriptions" table under the Stripe subscription ID.
     * Once an order is fulfilled (giving the user the desired role) the row is removed from the database.
     */
    index.mysqlConnection.query("SELECT * FROM new_subscriptions", function(err, result) {
        // simple error handling
        if(err) {
            task_handler.sendMessage(err.message, 3);
            return;
        }

        // if there are no new orders return
        if(result.length < 1) return;

        // iterate over all new orders in the table (these are deleted at the bottom once successfully processed)
        for(var i = 0; i < result.length; i++) {

            var sub_id = result[i].subscription_id;

            var guild_id = result[i].guild_id;
            var role_id = result[i].role_id;
            var customer_discord_id = result[i].customer_discord_id;
            var customer_id = result[i].customer_id;
            var partner_discord_id = result[i].partner_discord_id;
            var partner_id = result[i].partner_id;

            var guild = index.bot.guilds.cache.get(guild_id);
            var valid_guild = guild !== null && guild !== undefined;
            var role = valid_guild ? guild.roles.cache.get(role_id) : null;
            var valid_role = role !== null && role !== undefined;

            if(!valid_guild || !valid_role) {
                refund_handler.refundOrder(sub_id, null);
            } else {
                // try to get the user from the guild
                guild.members.fetch(customer_discord_id).then(guildMember => {
                    // try to add the desired role to the GuildMember
                    guildMember.roles.add(role_id).then(user => {
                        // let the user know the role was purchased successfully
                        user.send(`Purchase completed for the role ${role.name} in the ${guild.name} server! Role added.`);
                        deleteNewSub(sub_id);
                    }).catch(error => {
                        // if there is an error we need to refund and cancel the order and let the product owner know
                        deleteNewSub(sub_id);
                        refund_handler.refundOrder(sub_id, null);
                        // TODO: this doesnt seem to remove their subscription if it failed, still shows in subscriptions
                        if(error.message == 'Missing Permissions') {
                            // send the owner the message
                            guild.members.fetch(partner_discord_id).then(guildOwner => {
                                guildOwner.send(`Move Bot Role Higher (missing permissions). Failed to add the ${role.name} role to customer's account in the ${guild.name} server. Transaction cancelled. Order refunded.`)
                            });
                            // send the customer the message
                            guildMember.send(`Failed to add the ${role.name} role to your account in the ${guild.name} server. Transaction cancelled. Order refunded.`);
                        } else {
                            guildMember.send(`Failed to add the ${role.name} role to your account in the ${guild.name} server. Transaction cancelled. Order refunded.`);
                        }
                    });
                }).catch(error => {
                    // TODO: Auto accept invite to guild from bot to user
                    var invite = guild.channels.cache.array()[0].createInvite({
                        maxAge: 10 * 60 * 1000, // maximum time for the invite, in milliseconds
                        maxUses: 1 // maximum times it can be used
                    });

                    deleteNewSub(sub_id);
                    refund_handler.refundOrder(sub_id, null);

                    // if we are here then the user who purchased the role is not in the server
                    index.bot.users.fetch(customer_discord_id).then(user => {
                        user.send(`You are not in the ${guild.name} server. Transaction cancelled. Order refunded. Use the link below to join the server.`);
                        user.send(`${invite}`);
                    }).catch(error => {
                        console.log(error.message)
                    });
                });
            }
        }
    });
};

function end_subscriptions() {
    index.mysqlConnection.query("SELECT * FROM ended_subscriptions", function(err, result) {
        // simple error handling
        if(err) {
            task_handler.sendMessage(err.message, 3);
            return;
        }

        // if there are no new orders return
        if(result.length < 1) return;

        // iterate over all new orders in the table (these are deleted at the bottom once successfully processed)
        for(var i = 0; i < result.length; i++) {

            var sub_id = result[i].subscription_id;

            var guild_id = result[i].guild_id;
            var role_id = result[i].role_id;
            var customer_discord_id = result[i].customer_discord_id;
            var customer_id = result[i].customer_id;
            var partner_discord_id = result[i].partner_discord_id;
            var partner_id = result[i].partner_id;

            var guild = index.bot.guilds.cache.get(guild_id);
            var valid_guild = guild !== null && guild !== undefined;
            var role = valid_guild ? guild.roles.cache.get(role_id) : null;
            var valid_role = role !== null && role !== undefined;

            if(valid_guild && valid_role) {
                // try to get the user from the guild
                guild.members.fetch(customer_discord_id).then(guildMember => {
                    // try to remove the desired role to the GuildMember
                    guildMember.roles.remove(role_id).then(user => {
                        // let the user know the role was purchased successfully
                        user.send(`Subscription ended for the role ${role.name} in the ${guild.name} server. Role removed.`);
                        deleteEndedSub(sub_id);
                    }).catch(error => {
                        guild.members.fetch(partner_discord_id).then(guildOwner => {
                            if(error.message == 'Missing Permissions') {
                                // send the owner the message
                                guildOwner.send(`Move Bot Role Higher (missing permissions). Failed to remove the ${role.name} role from customer's account in the ${guild.name} server. Their subscription has expired. Trying again in 10 minutes.`)
                            } else {
                                guildOwner.send(`Failed to remove the ${role.name} role from customer's account in the ${guild.name} server. Their subscription has expired. Trying again in 10 minutes.`);
                            }
                        });
                    });
                }).catch(error => {
                    guild.members.fetch(partner_discord_id).then(guildOwner => {
                        guildOwner.send(`Failed to remove the ${role.name} role from customer's account in the ${guild.name} server. Their subscription has expired. Trying again in 10 minutes.`);
                    });
                });
            }
        }
    });
}

async function deleteNewSub(subscription_id) {
    index.mysqlConnection.query(`DELETE FROM new_subscriptions WHERE subscription_id='${subscription_id}'`, function (err, result) {
        // simple error handling
        if (err) {
            task_handler.sendMessage(err.message, 3);
            console.log(err);
            return;
        }
    });
}

async function deleteEndedSub(subscription_id) {
    index.mysqlConnection.query(`DELETE FROM ended_subscriptions WHERE subscription_id='${subscription_id}'`, function (err, result) {
        // simple error handling
        if (err) {
            task_handler.sendMessage(err.message, 3);
            console.log(err);
            return;
        }
    });
}