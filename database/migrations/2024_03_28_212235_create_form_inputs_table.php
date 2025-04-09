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
        Schema::create('form_inputs', function (Blueprint $table) {
            $table->id();
            $table->string('patient_phone', 15);
            $table->string('secondary_phone', 15)->nullable();
            $table->string('first_name', 15);
            $table->string('last_name', 15);
            $table->date('dob');
            $table->string('medicare_id', 15)->unique();
            $table->text('address');
            $table->string('city', 15);
            $table->string('state', 15);
            $table->string('zip', 15);
            $table->text('product_specs');
            $table->string('doctor_name', 30);
            $table->string('facility_name', 20);
            $table->string('patient_last_visit', 20);
            $table->text('doctor_address')->nullable();
            $table->string('doctor_phone', 15);
            $table->string('doctor_fax', 20);
            $table->string('doctor_npi', 50);
            $table->text('recording_link');
            $table->text('comments');
            $table->string('status', 10)->nullable();
            $table->foreignId('insurance_id');
            $table->foreignId('products_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_inputs');
    }
};
