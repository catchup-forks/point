<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->nullable()->unique();
            $table->string('name');
            $table->string('personal_identity')->nullable();
            // Data related to personal info
            $table->string('last_education')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->unsignedInteger('employee_gender_id')->nullable()->index();
            $table->unsignedInteger('employee_marital_status_id')->nullable()->index();
            $table->string('married_with')->nullable();
            $table->unsignedInteger('employee_religion_id')->nullable();
            // Data related to job
            $table->unsignedInteger('employee_group_id')->nullable()->index();
            $table->datetime('join_date')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedInteger('kpi_template_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->index()->nullable();
            $table->unsignedInteger('updated_by')->index()->nullable();
            $table->timestamps();

            // Relationship
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('employee_group_id')->references('id')->on('employee_groups')->onDelete('set null');
            $table->foreign('employee_gender_id')->references('id')->on('employee_genders')->onDelete('set null');
            $table->foreign('employee_marital_status_id')->references('id')->on('employee_marital_statuses')->onDelete('set null');
            $table->foreign('employee_religion_id')->references('id')->on('employee_religions')->onDelete('set null');
            $table->foreign('kpi_template_id')->references('id')->on('kpi_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employees');
    }
}
