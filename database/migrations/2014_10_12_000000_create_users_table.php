<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
            {
                Schema::create('users', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->string('first_name')->nullable();
                    $table->string('last_name')->nullable();
                    $table->string('company_name')->nullable();
                    $table->string('trade_license')->nullable();
                    $table->string('trn_certificate')->nullable();
                    $table->enum('user_type', ['personal', 'corporate'])->default('personal');
                    $table->string('name')->default('');
                    $table->string('email')->nullable()->unique();
                    $table->text('password');
                    $table->integer('code')->nullable();
                    $table->string('google_id')->nullable();
                    $table->string('facebook_id')->nullable();
                    $table->boolean('verified')->default(false);
                    $table->boolean('remember_token')->default(false);
                    $table->integer('default_address')->nullable();
                    $table->string('phone')->nullable();
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
        Schema::dropIfExists('users');
    }
}
