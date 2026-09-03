<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill usernames for existing users based on email prefix
        $users = DB::table('users')->whereNull('username')->get();
        foreach ($users as $user) {
            $prefix = explode('@', $user->email)[0];
            $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $prefix));
            if (empty($baseUsername)) {
                $baseUsername = strtolower(preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $user->name));
            }
            if (empty($baseUsername)) {
                $baseUsername = 'user' . $user->id;
            }

            $username = $baseUsername;
            $count = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $count;
                $count++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
