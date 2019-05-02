<?php

use JoeDixon\Translation\Language;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection(config('translation.database.connection'))
            ->create(config('translation.database.languages_table'), function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('language');
                $table->timestamps();
            });

        Language::create([
            'language' => config('app.locale'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection(config('translation.database.connection'))
            ->dropIfExists(config('translation.database.languages_table'));
    }
}
