<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColorColumns('product_variants');

        DB::table('product_variants')->whereNull('color_hex')->update([
            'color_hex' => '#1C1C1C',
        ]);

        $this->replaceSizeUniqueWithColorUnique();

        $this->addColorColumns('order_items');

        DB::table('order_items')->whereNull('color_hex')->update([
            'color_hex' => '#1C1C1C',
        ]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'size', 'color_ar']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['color_ar', 'color_en', 'color_hex']);
            $table->unique(['product_id', 'size']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['color_ar', 'color_en', 'color_hex']);
        });
    }

    private function addColorColumns(string $table): void
    {
        if (Schema::hasColumn($table, 'color_ar')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('color_ar', 40)->default('أسود')->after('size');
            $blueprint->string('color_en', 40)->default('Black')->after('color_ar');
            $blueprint->string('color_hex', 7)->nullable()->after('color_en');
        });
    }

    private function replaceSizeUniqueWithColorUnique(): void
    {
        $indexes = collect(Schema::getIndexes('product_variants'));
        $foreign = collect(Schema::getForeignKeys('product_variants'))
            ->first(fn (array $key) => $key['columns'] === ['product_id']);

        if ($foreign !== null) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        }

        if ($indexes->contains(fn (array $index) => $index['name'] === 'product_variants_product_id_size_unique')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropUnique(['product_id', 'size']);
            });
        }

        $indexes = collect(Schema::getIndexes('product_variants'));
        $hasProductIndex = $indexes->contains(
            fn (array $index) => $index['columns'] === ['product_id'] && ! $index['unique'] && ! $index['primary']
        );
        $hasColorUnique = $indexes->contains(
            fn (array $index) => $index['name'] === 'product_variants_product_id_size_color_ar_unique'
        );

        Schema::table('product_variants', function (Blueprint $table) use ($hasProductIndex, $hasColorUnique) {
            if (! $hasProductIndex) {
                $table->index('product_id');
            }

            if (! $hasColorUnique) {
                $table->unique(['product_id', 'size', 'color_ar']);
            }

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
