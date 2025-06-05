<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('graphql_subscriptions', function (Blueprint $table) {
			$table->uuid('id')->primary();

			$table->string('channel')
				->index();
			$table->string('transport');
			$table->string('schema_name');
			$table->json('document');
			$table->json('variables');
			$table->text('resolve')->nullable();
			$table->text('filter')->nullable();
			$table->timestamp('expires_at')->nullable();

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::drop('graphql_subscriptions');
	}
};
