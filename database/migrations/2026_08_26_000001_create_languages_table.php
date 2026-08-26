<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();   // 'id', 'en', 'zh', etc.
            $table->string('name', 100);             // 'Indonesia', 'English'
            $table->string('native_name', 100);      // 'Bahasa Indonesia', 'English'
            $table->string('flag_emoji', 10)->nullable(); // '🇮🇩'
            $table->string('flag_code', 10)->nullable();  // 'ID' (ISO country for flag CSS)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed defaults
        DB::table('languages')->insert([
            ['code'=>'id','name'=>'Indonesia','native_name'=>'Bahasa Indonesia','flag_emoji'=>'🇮🇩','flag_code'=>'ID','is_active'=>true,'is_default'=>true,'sort_order'=>0,'created_at'=>now(),'updated_at'=>now()],
            ['code'=>'en','name'=>'English','native_name'=>'English','flag_emoji'=>'🇬🇧','flag_code'=>'GB','is_active'=>true,'is_default'=>false,'sort_order'=>1,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
