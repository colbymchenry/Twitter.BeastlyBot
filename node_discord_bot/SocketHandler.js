const app = require('express')();

let https = null;
let credentials = {};

if (process.env.DEV_ENV === 'false') {
    console.log('IN PRODUCTION');
    https = require('https');
    const fs = require('fs');
    credentials = {
        key: fs.readFileSync('/etc/letsencrypt/live/colbymchenry.com/privkey.pem').toString(),
        cert: fs.readFileSync('/etc/letsencrypt/live/colbymchenry.com/cert.pem').toString(),
        ca: fs.readFileSync('/etc/letsencrypt/live/colbymchenry.com/chain.pem').toString(),
        requestCert: true,
        rejectUnauthorized: false
    };
} else {
    console.log('IN DEVELOPMENT');
    https = require('http');
}

let httpsServer = null;

if (process.env.DEV_ENV === 'false') {
    httpsServer = https.createServer(credentials, app);
} else {
    httpsServer = https.createServer(app);
}

const io = require('socket.io')(httpsServer);
const bot = require('./index.js');

module.exports = {start};

// const cache_import = require('memory-cache');
// let cache = new cache_import.Cache();

const NodeCache = require( "node-cache" );
const cache = new NodeCache();

function start() {
    // start listening on port 3000
    httpsServer.listen(process.env.SOCKET_PORT, function () {
        console.log(`Socket.IO up and running on port ${process.env.SOCKET_PORT}!`);
    });

    io.on('connection', function (socket) {
        socket.on('toggle_role_activity', function(data) {
            var guild_id = data[1];
            var role_id = data[2];
            var status = data[3] == 'true';
            console.log(status);

            var key = `get_role_for_sale_${guild_id}_${role_id}`;

            cache.set(key, status.toString(), 5);
        });

        // cached (1 minute) 5 seconds
        socket.on('get_role_for_sale', function (data) {
            var guild_id = data[1];
            var role_id = data[2];

            var key = `get_role_for_sale_${guild_id}_${role_id}`;
            var role_for_sale = cache.get(key);

            if(role_for_sale) {
                io.emit('res_role_for_sale_' + data[0], {
                    'guild_id': guild_id,
                    'role_id': role_id,
                    'for_sale': (role_for_sale == 'true')
                });
            } else {
                // TODO: Some how check if product is enabled before searching or we get a log error, maybe with this sql check?
                // bot.mysqlConnection.query(`SELECT FROM products WHERE guild='${guild_id}' AND role='${role_id}';`, function (err, result) {
                bot.stripe.products.retrieve(
                    guild_id + '_' + role_id,
                    function (err, product) {
                        // asynchronously called
                        if (err === null) {
                            var has_plans = false;
                            var plans_total = 0;
                            for (var i = 0; i < 13; i++) {
                                if (i === 1 || i === 3 || i === 6 || i === 12) {
                                    bot.stripe.plans.retrieve(
                                        guild_id + '_' + role_id + '_' + i + '_r',
                                        function (err, plan) {
                                            if (err === null && plan !== null) {
                                                if (!has_plans || plans_total == 0) {
                                                    io.emit('res_role_for_sale_' + data[0], {
                                                        'guild_id': guild_id,
                                                        'role_id': role_id,
                                                        'for_sale': product.active
                                                    });
                                                    cache.set(key, product.active.toString(), 5);
                                                    /*stripe.products.update(
                                                        guild_id + '_' + role_id,
                                                        {active: false},
                                                        function(err, product) {
                                                           // has_plans = false;
                                                        }
                                                    );*/
                                                }
                                                has_plans = true;
                                                plans_total += plan['amount'];
                                            }
                                        }
                                    );
                                }
                            }
                            if (has_plans == true && plans_total == 0){
                                bot.stripe.products.update(
                                    guild_id + '_' + role_id,
                                    {active: false},
                                    function(err, product) {
                                       // has_plans = false;
                                    }
                                  );
                            }
                        } else {
                            io.emit('res_role_for_sale_' + data[0], {
                                'guild_id': guild_id,
                                'role_id': role_id,
                                'for_sale': false
                            });
                            cache.set(key, false.toString(), 5);
                        }
                    }
                );
            }
        });

        // cached (5 minutes)
        socket.on('is_user_banned', function(data) {
            var socket_id = data[0];
            var user_id = data[2];

            var key = `bans_${data[1]}`;
            var bans = cache.get(key);

            if(bans) {
                bans.forEach((value, key, map) => {
                    if(key == user_id) io.emit('res_user_banned_' + socket_id, true);
                });
            } else {
                bot.bot.guilds.cache.get(data[1]).fetchBans().then(bans => {
                    cache.set(key, bans, 60 * 5);
                    bans.forEach((value, key, map) => {
                        if(key == user_id) io.emit('res_user_banned_' + socket_id, true);
                    });
                }).catch(console.error);
            }
        });

        // cached (1 minute)
        socket.on('is_user_in_guild', function(data) {
            var socket_id = data[0];
            var user_id = data[2];

            var key = `users_${data[1]}`;
            var users = cache.get(key);

            if(users) {
                io.emit('res_user_in_guild_' + socket_id, users.cache.has(user_id));
            } else {
                cache.set(key, bot.bot.guilds.cache.get(data[1]).members, 60);
                users = cache.get(key);
                io.emit('res_user_in_guild_' + socket_id, users.cache.has(user_id));
            }
        });

        // cached (2 minutes)
        socket.on('get_roles', function (data) {
            var key = `roles_${data[1]}`;
            var roles = cache.get(key);

            if(roles) {
                io.emit('res_roles_' + data[0], roles);
            } else {
                cache.set(key, bot.getRoles(data[1]), 120);
                io.emit('res_roles_' + data[0], cache.get(key));
            }
        });

        // cached (1 minute)
        socket.on('get_guilds', function (data) {
            var key = `guilds_${data[1]}`;
            var guilds = cache.get(key);


            if(guilds) {
                io.emit('res_guilds_' + data[0], guilds);
            } else {
                cache.set(key, bot.getGuilds(data[1]), 60);
                io.emit('res_guilds_' + data[0], cache.get(key));
            }
        });

        // cached (30 minutes)
        socket.on('get_guild_subs', function (d) {
            // grab the guild
            let guild = bot.bot.guilds.cache.get(d[1]);
            // if the guild doesn't exist cancel the function
            if (guild === undefined || guild === null) return;

            var roles_to_check = [];

            // find any role that isn't a bots role and exlude the @everyone role for roles to check for subscribers
            for (let role_index in guild.roles.cache.array()) {
                let role = guild.roles.cache.array()[role_index];
                if (role.name !== '@everyone' && !role.bot && !role.managed)
                    roles_to_check.push(role);
            }

            async function fillSubscriptionData(guild, role, data, duration) {
                var key = `${guild.id}_${role.id}_${duration}_r`;

                let criteria = {
                    plan: key,
                    status: 'active'
                };

                let role_data = {
                    id: role.id,
                    color: role.hexColor,
                    name: role.name
                };

                // TODO: So storing in the DB works if it is synced properly with the Stripe DB.
                // Will not be using the DB until we find a better way to sync them or we get a rate limitation.
                // var does_exist = await doesProductExist(guild, role, duration);

                // if(!does_exist) {
                //     console.log("PRODUCT DOES NOT EXIST." + criteria);
                //     return undefined;
                // }

                var cached_subs = cache.get(key);

                if(cached_subs) {
                    await fillData(role, role_data, cached_subs, data);
                    return cached_subs;
                }

                return await bot.stripe.subscriptions.list(criteria).then(async function(subscriptions) {
                    await fillData(role, role_data, subscriptions, data);
                    cache.set(key, subscriptions, 60 * 60 * 0.5);
                    return subscriptions;
                }).catch(err => {
                    return undefined;
                });


            }

            async function fillData(role, role_data, subscriptions, data) {
                for(var subscription_index in subscriptions.data) {
                    var subscription = subscriptions.data[subscription_index];
                    var discord_id = subscription.metadata['discord_id'];

                    if(discord_id in data) {
                        var roles = data[discord_id];
                        if(!(role.id in roles)) {
                            roles[role.id] = role_data;
                            data[discord_id] = roles;
                        }
                    } else {
                        var roles = {};
                        roles[role.id] = role_data;
                        data[discord_id] = roles;
                    }
                }

            }

            async function doesProductExist(guild, role, duration) {
                return new Promise( ( resolve, reject ) => {
                    bot.mysqlConnection.query(`SELECT * FROM products WHERE guild='${guild.id}' AND role='${role.id}' AND duration='${duration}';`, function (err, result) {
                        if (err) {
                            sendMessage(err.message, 3);
                            console.log(err);
                            return;
                        }

                        if (result.length < 1) resolve(false);
                        resolve(true);
                    });
                } );
            }

            async function startChurning(guild, roles_to_check) {
                var data = {};
                data['id'] = guild.id;

                for(let role_index = 0; role_index < roles_to_check.length; role_index++) {
                    let role = roles_to_check[role_index];
                    await finalize(guild, role, data);
                }

                return data;
            }

            async function finalize(guild, role, data) {
               return await Promise.all([
                    fillSubscriptionData(guild, role, data, 1),
                    fillSubscriptionData(guild, role, data, 3),
                    fillSubscriptionData(guild, role, data, 6),
                    fillSubscriptionData(guild, role, data, 12)
                  ]).then(function(vals) {
                    return vals;
                  }).catch(err => {
                      console.log("ERROR!?");
                      console.log(err);
                      return undefined;
                  });
            }

            startChurning(guild, roles_to_check).then(function(data) {
                var result = data;
                result['count'] = Object.keys(data).length - 1;
                io.emit(`res_guild_subs_${d[0]}`, result);
            });
        });

        // cached (1 minute)
        socket.on('get_guild_data', function (data) {
            var key = `guild_data_${data[1]}`;
            var guild_data = cache.get(key);

            if(guild_data) {
                io.emit('res_guild_data_' + data[0], guild_data);
            } else {
                cache.set(key, bot.getGuildData(data[1]), 60);
                io.emit('res_guild_data_' + data[0], bot.getGuildData(data[1]));
            }
        });

        // cached (1 minute)
        socket.on('get_role_data', function (data) {
            var key = `guild_data_${data[1]}_${data[2]}`;
            var role_data = cache.get(key);

            if(role_data) {
                io.emit('res_role_data_' + data[0], role_data);
            } else {
                cache.set(key, bot.getRoleData(data[1], data[2]), 60);
                io.emit('res_role_data_' + data[0], bot.getRoleData(data[1], data[2]));
            }
        });

        // cached (1 minute)
        socket.on('get_user_data', function (data) {
            var key = `get_user_data_${data[1]}`;
            var user_data = cache.get(key);

            if(user_data) {
                io.emit('res_user_data_' + data[0], user_data);
            } else {
                bot.bot.users.fetch(data[1]).then(user => {
                    var avatarURL = 'https://i.imgur.com/qbVxZbJ.png';
                    if(user.avatar !== null) avatarURL = `https://cdn.discordapp.com/avatars/${user.id}/${user.avatar}.png`;
                    var d = {
                        avatar: avatarURL,
                        name: user.username,
                        discriminator: user.discriminator,
                        id: user.id
                    };

                    cache.set(key, d, 60)
                    io.emit('res_user_data_' + data[0], d);
                }).catch(error => {
                });
            }

        });

        // cached (30 minutes)
        socket.on('get_other_guilds', function (data) {
            var key = `get_other_guilds_${data[1]}`;
            var guilds = cache.get(key);
        
        
            if(guilds) {
                io.emit('res_other_guilds_' + data[0], guilds);
            } else {
                cache.set(key, bot.getOtherGuilds(data[1]), 60 * 60 * 0.5);
                io.emit('res_other_guilds_' + data[0], cache.get(key));
            }
        });

        
    });

}

