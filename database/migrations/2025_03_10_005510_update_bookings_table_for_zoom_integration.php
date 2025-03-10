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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('host_user_id')->nullable()->after('title');
            $table->string('participant_email')->nullable()->after('host_user_id');
            $table->string('zoom_meeting_id')->nullable()->after('end');
            $table->string('zoom_meeting_url')->nullable()->after('zoom_meeting_id');
            $table->string('zoom_meeting_password')->nullable()->after('zoom_meeting_url');
            $table->boolean('waiting_room')->default(true)->after('zoom_meeting_password');
            $table->integer('max_participants')->default(10)->after('waiting_room');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'host_user_id',
                'participant_email',
                'zoom_meeting_id',
                'zoom_meeting_url',
                'zoom_meeting_password',
                'waiting_room',
                'max_participants'
            ]);
        });
    }
};
