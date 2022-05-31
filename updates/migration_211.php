<?php namespace Jacob\Logbook\Updates;

use October\Rain\Database\Schema\Blueprint;
use Schema;
use October\Rain\Database\Updates\Migration;

class Migration211 extends Migration
{
    public function up()
    {
         Schema::table('jacob_logbook_logs', function(Blueprint $table) {
             $table->mediumText('changes')->change();
         });
    }

    public function down()
    {
        Schema::table('jacob_logbook_logs', function(Blueprint $table) {
            $table->text('changes')->change();
        });
    }
}