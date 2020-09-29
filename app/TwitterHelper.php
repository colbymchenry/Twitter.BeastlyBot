<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use League\OAuth2\Client\Token\AccessToken;
use Wohali\OAuth2\Client\Provider\Discord;
use RestCord\DiscordClient;
use Illuminate\Support\Facades\Log;

class TwitterHelper
{

    private $minutes_to_cache = 10;
    private $user;
    private $twitter_account;

    public function __construct(User $user){
        $this->user = $user;
        $this->twitter_account = TwitterAccount::where('user_id', $user->id)->first();
    }

    public function cache(): void {
        // $data = $this->getDiscordData();
        // $username = $data['username'] . ' #' . $data['discriminator'];

        // if(!empty($this->getDiscordData()['avatar'])) {
        //     $avatar_url = "https://cdn.discordapp.com/avatars/" . $this->user->DiscordOAuth->discord_id . "/" . $this->getDiscordData()['avatar'] . ".png";
        // } else {
        //     $avatar_url = 'https://i.imgur.com/qbVxZbJ.png';
        // }

        // Cache::put('discord_username_' . $this->user->DiscordOAuth->discord_id, $username, 60 * $this->minutes_to_cache);
        // Cache::put('discord_email_' . $this->user->DiscordOAuth->discord_id, $data['email'], 60 * $this->minutes_to_cache);
        // Cache::put('discord_avatar_' . $this->user->DiscordOAuth->discord_id, $avatar_url, 60 * $this->minutes_to_cache);
    }

    public function getID(): string {
        return $this->twitter_account->twitter_id;
    }

    public function getAvatar(): string {
        return $this->twitter_account->profile_image;
    }

    public function getUsername(): string {
        return $this->twitter_account->screen_name;
    }

    public function getEmail(): string {
        return "null@gmail.com";
    }

    public function getGuilds() {
        return array();
    }
    
    public function getOwnedGuilds() {
        return array();
    }

    public function guildHasBot(int $guild_id) {
        return false;
    }

    public function isUserBanned(int $guild_id, int $user_id) {
        return false;
    }

    public function isMember(int $guild_id, int $user_id) {
        return false;
    }

    public function getRoles(int $guild_id) {
        return array();
    }

    public function getRole(int $guild_id, int $role_id) {
        return null;
    }

    public function getGuild(int $guild_id) {
        return null;
    }

    public function ownsGuild(int $guild_id): bool {
        return false;
    }

    public function getUser(int $discord_id) {
        return null;
    }

    public function sendMessage($message) {
    }

    public function isBotPositioned(int $guild_id) {
        return true;
    }

}
