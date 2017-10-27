<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEasypostWebhookCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('easypost_webhook_calls', function (Blueprint $table) {
            $table->increments('id');

            $table->string('description')->nullable();
            $table->text('payload')->nullable();
            $table->text('exception')->nullable();

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
        Schema::dropIfExists('easypost_webhook_calls');
    }
}
