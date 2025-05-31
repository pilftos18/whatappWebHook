<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMastermoduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_list', function (Blueprint $table) {
            $table->id();
            $table->string('apiname', 100);
            $table->string('company', 100); 
            $table->string('vendorname', 100);
            $table->string('apiurl', 100);
            $table->string('apitesturl', 100)->nullable(); 
            $table->softDeletes();
            $table->enum('status', ['1', '2'])->default('1');
            $table->integer('del_status')->default('1');
            $table->string('created_by', 100)->nullable(); 
            $table->string('updated_by', 100)->nullable(); 
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
        Schema::dropIfExists('mastermodule');
    }
}
