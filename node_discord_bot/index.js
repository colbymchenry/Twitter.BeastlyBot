#!/usr/bin/env node
require('dotenv').config();

const commando = require('discord.js-commando');
// const path = require('path');
const bot = new commando.Client();
// const mysql = require('mysql');
// const stripe = require("stripe")(process.env.STRIPE_KEY);

// var mysqlConnection = null;
// if(process.env.MYSQL_SOCKET != null) {
//     mysqlConnection = mysql.createConnection({
//         host: process.env.MYSQL_HOST,
//         database: process.env.MYSQL_DB,
//         user: process.env.MYSQL_USER,
//         password: process.env.MYSQL_PASS,
//         socketPath: process.env.MYSQL_SOCKET,
//         supportBigNumbers: true,
//         bigNumberStrings: true
//     });
//     console.log('Connected to MySQL Socket! (DB: ' + process.env.MYSQL_DB + ')');
// } else {
//     mysqlConnection =  mysql.createConnection({
//         host: process.env.MYSQL_HOST,
//         database: process.env.MYSQL_DB,
//         user: process.env.MYSQL_USER,
//         password: process.env.MYSQL_PASS,
//         supportBigNumbers: true,
//         bigNumberStrings: true
//     });
//     console.log('Connected to MySQL! (DB: ' + process.env.MYSQL_DB + ')');
// }

// bot.registry.registerGroup('random', 'Random');
// bot.registry.registerCommandsIn(__dirname + "/commands");

bot.login(process.env.DISCORD_KEY);

// module.exports = {getRoles, getGuilds, getGuildData, getRoleData, bot, mysqlConnection, stripe, getOtherGuilds};

// Start the web socket
// var socketHandler = require('./SocketHandler.js');
// socketHandler.start();
// // Start the PayPal loop
// var taskHandler = require('./TaskHandler.js');
// taskHandler.startLoopLong();
// taskHandler.startLoopShort();

/**
 * Called when the bot is done initializing
 */
bot.on('ready', () => {
});

// function getGuilds(user_id) {
//     let guilds = {};
//     bot.guilds.cache.forEach(g => {
//         if (g.owner.id === user_id) {
//             if (g.icon !== null && g.icon !== undefined){
//                 guilds[g.id] = {
//                     name: g.name,
//                     iconURL: `https://cdn.discordapp.com/icons/${g.id}/${g.icon}.png?size=256`,
//                     memberCount: g.memberCount,
//                     owner: g.ownerID
//                 }
//             }else{
//                 guilds[g.id] = {
//                     name: g.name,
//                     iconURL: `https://discordapp.com/assets/322c936a8c8be1b803cd94861bdfa868.png`,
//                     memberCount: g.memberCount,
//                     owner: g.ownerID
//                 }
//             }
//         }
//     });

//     return guilds;
// }

// function getRoles(guild_id) {
//     var guild = bot.guilds.cache.get(guild_id);
//     let roles = {};

//     if (guild !== null && guild !== undefined) {
//         guild.roles.cache.forEach(role => {
//             if (role.name !== '@everyone' && !role.managed && !role.bot) {
//                 roles[role.id] = {
//                     color: role.hexColor,
//                     name: role.name,
//                     permissions: role.permissions,
//                     position: role.position,
//                     guild_id: guild.id,
//                     memberCount: getMemberCount(guild, role).toString(),
//                     role_id: role.id
//                 };
//             }
//         });
//     }

//     return roles;
// }

// function getGuildData(id) {
//     var guild = bot.guilds.cache.get(id);
//     var guild_data = {};

//     if (guild !== null && guild !== undefined) {
//         if (guild.icon !== null && guild.icon !== undefined){
//             guild_data = {
//                 name: guild.name,
//                 iconURL: `https://cdn.discordapp.com/icons/${guild.id}/${guild.icon}.png?size=256`,
//                 memberCount: guild.memberCount,
//                 id: guild.id,
//                 owner: guild.ownerID
//             };
//         }else{
//             guild_data = {
//                 name: guild.name,
//                 iconURL: `https://discordapp.com/assets/322c936a8c8be1b803cd94861bdfa868.png`,
//                 memberCount: guild.memberCount,
//                 id: guild.id,
//                 owner: guild.ownerID
//             };
//         }
//     }
//     return guild_data;
// }

// function getRoleData(guild_id, role_id) {
//     var guild = bot.guilds.cache.get(guild_id);
//     var data = {};
//     if (guild !== null && guild !== undefined) {
//         var role = guild.roles.cache.get(role_id);
//         if (role !== null && role !== undefined) {
//             data = {
//                 id: role.id,
//                 name: role.name,
//                 color: role.hexColor,
//                 memberCount: role.members.size,
//                 guild_id: guild_id,
//                 guild_name: guild.name
//             };
//         }
//     }

//     return data;
// }

// function getMemberCount(guild, role) {
//     var count = 0;
//     guild.members.cache.forEach(member => {
//         if (member.roles.cache.has(role.id)) count++;
//     });
//     return count;
// }

// function getOtherGuilds(user_id) {
//     let other_guilds = {};
//     bot.guilds.cache.forEach(g => {
//         $shop = false;
//         if (g.owner.id != user_id) {
//         mysqlConnection.query("SELECT * FROM discord_shops WHERE discord_id='" + g.id + "';", function (err, result) {
//             if (err) {
//                 $shop = false;
//             }else{
//                 $shop = true;
//             }
//         });
//             if (g.icon !== null && g.icon !== undefined){
//                 other_guilds[g.id] = {
//                     name: g.name,
//                     iconURL: `https://cdn.discordapp.com/icons/${g.id}/${g.icon}.png?size=256`,
//                     memberCount: g.memberCount,
//                     owner: g.ownerID,
//                     id: g.id,
//                     shop: $shop, // way to check if enabled or not
//                 }
//             }else{
//                 other_guilds[g.id] = {
//                     name: g.name,
//                     iconURL: `https://discordapp.com/assets/322c936a8c8be1b803cd94861bdfa868.png`,
//                     memberCount: g.memberCount,
//                     owner: g.ownerID,
//                     id: g.id,
//                     shop: $shop,
//                 }
//             }        
//         }
//     })
//     return other_guilds;
// }