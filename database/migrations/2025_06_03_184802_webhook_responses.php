<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("webhook_responses", function (Blueprint $table) {
            $table->id();
            $table->string("webhook_request_id");
            $table->integer("status_code")->index();
            $table->text("headers")->nullable();
            $table->longText("body")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
