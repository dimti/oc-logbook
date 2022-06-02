<?php namespace Jacob\Logbook\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class Migration212 extends Migration
{
    public function up()
    {
         Schema::table('jacob_logbook_logs', function(Blueprint $table) {
             $table->timestamp('deleted_at')->nullable()->index();
         });
    }

    public function down()
    {
        Schema::table('jacob_logbook_logs', function(Blueprint $table) {
            $table->dropColumn('deleted_at');
        });
    }
}