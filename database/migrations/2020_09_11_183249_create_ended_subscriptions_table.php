<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEndedSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ended_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_id');
            $table->string('partner_id');
            $table->string('partner_discord_id');
            $table->string('customer_id');
            $table->string('customer_discord_id');
            $table->string('guild_id');
            $table->string('role_id');
            $table->string('reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ended_subscriptions');
    }
}
