<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBillableFields extends Migration
{

    protected $billableTableName;

    /**
     * Our billable model's table name must be set in here for usage of tables.
     */
    public function __construct()
    {
        $billableModelName       = config('iyzipay.billableModel');
        $this->billableTableName = (new $billableModelName)->getTable();
    }

    public function up()
    {
        Schema::table($this->billableTableName, function (Blueprint $table) {
            $table->longText('bill_fields')->nullable();
            $table->string('iyzipay_key')->nullable();
        });
    }

    public function down()
    {
        Schema::table($this->billableTableName, function (Blueprint $table) {
            $table->dropColumn(['bill_fields', 'iyzipay_key']);
        });
    }

}