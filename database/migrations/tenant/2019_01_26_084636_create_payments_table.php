<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('id');
            // payment type should be payment-order, payment-collection, cash, bank or cheque
            $table->string('payment_type');
            // if this payment type is payment-order or payment-collection then we can set due date
            $table->date('due_date')->nullable();
            $table->boolean('disbursed');
            $table->decimal('amount', 65, 30);
            // with who we make / receive payment
            // it can be supplier / customer / employee
            $table->unsignedInteger('paymentable_id')->nullable();
            $table->string('paymentable_type')->nullable();
            $table->string('paymentable_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
