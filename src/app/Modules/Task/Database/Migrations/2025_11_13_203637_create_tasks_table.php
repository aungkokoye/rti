<?php
declare(strict_types=1);

use App\Models\User;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'assigned_to')->nullable()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending')->index();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium')->index();
            $table->unsignedInteger('version');
            $table->json('metadata')->nullable();
            $table->dateTime('due_date')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });


        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role')->after('remember_token')->default(0);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');


        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

    }
};
