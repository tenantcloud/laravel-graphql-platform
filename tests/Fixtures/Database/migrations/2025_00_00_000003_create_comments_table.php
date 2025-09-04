<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\Valid\Models\Eloquent\Comment;
use Tests\Fixtures\Valid\Models\Eloquent\Post;

return new class () extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('comments', function (Blueprint $table) {
			$table->id();
			$table->foreignIdFor(Post::class);
			$table->foreignIdFor(Comment::class, 'parent_id')
				->nullable();
			$table->text('content');
			$table->float('rating')
				->unsigned()
				->nullable();
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::drop('blogs');
	}
};
