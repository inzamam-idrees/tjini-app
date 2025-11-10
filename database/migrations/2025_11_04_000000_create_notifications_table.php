<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users')->onDelete('cascade');
            $table->string('type');
            $table->text('message');
            $table->string('value')->nullable();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('all_parents')->default(false);
            $table->string('sender_role'); // parent, dispatcher, viewer
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};