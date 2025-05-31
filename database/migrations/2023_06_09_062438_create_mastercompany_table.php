<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMastercompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->nullable(); // Set email column as nullable
            $table->string('website', 100);
            $table->string('file', 100)->nullable(); // Set file column as nullable
            $table->softDeletes();
            $table->enum('status', ['1', '2'])->default('1');
            $table->integer('del_status')->default('1');
            $table->string('created_by', 100)->nullable(); // Set created_by column as nullable
            $table->string('updated_by', 100)->nullable(); // Set updated_by column as nullable
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
        Schema::dropIfExists('mastercompany');
    }
}
