<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateUsersTable extends Migration
{

    public function up()
    {
        Schema::create('users', function(Blueprint $table)
        {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('birthdate');
            $table->integer('gender');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('vcode',50);
        });
    }
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
