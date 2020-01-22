<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('billable_id')->index();
            $table->double('next_charge_amount')->default(0);
            $table->string('currency')->default('try');
            $table->timestamp('next_charge_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->longText('plan');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
