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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('CIN');
            $table->text('address')->nullable();
            $table->string('anekk_id')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person_email');
            $table->string('contact_person_landline')->nullable();
            $table->string('contact_person_mobile');
            $table->string('display_organization_name');
            $table->string('holiday_calender');
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('last_login')->nullable();
            $table->timestamp('last_logout')->nullable();
            $table->string('legal_entity_name');
            $table->string('organization_name');
            $table->string('password')->nullable();
            $table->string('registered_address');
            $table->json('selected_module')->nullable();
            $table->string('senior_person_email');
            $table->string('senior_person_mobile');
            $table->string('sub_category');
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
