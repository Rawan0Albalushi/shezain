<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@shezain.om')],
            [
                'name' => 'شيزين',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $categories = [
            ['slug' => 'formal', 'name_ar' => 'عبايات رسمية', 'name_en' => 'Formal abayas', 'description_ar' => 'أناقة متزنة لإطلالات العمل والمناسبات الرسمية.', 'description_en' => 'Refined elegance for work and formal occasions.', 'sort' => 1],
            ['slug' => 'winter', 'name_ar' => 'عبايات شتوية', 'name_en' => 'Winter abayas', 'description_ar' => 'قصّات شتوية بتفاصيل تمنح إطلالتكِ الدفء.', 'description_en' => 'Winter silhouettes with thoughtfully crafted details.', 'sort' => 2],
            ['slug' => 'summer', 'name_ar' => 'عبايات صيفية', 'name_en' => 'Summer abayas', 'description_ar' => 'إطلالات صيفية خفيفة بتفاصيل ناعمة.', 'description_en' => 'Light summer styles with a soft, effortless feel.', 'sort' => 3],
            ['slug' => 'vip', 'name_ar' => 'عبايات VIP', 'name_en' => 'VIP abayas', 'description_ar' => 'مجموعة مميزة لحضور يليق بمناسباتكِ.', 'description_en' => 'A distinctive collection for your special occasions.', 'sort' => 4],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }

        $products = [
            ['slug' => 'layali', 'category' => 'formal', 'name_ar' => 'عباية ليالي', 'name_en' => 'Layali abaya', 'price' => 55000, 'image' => 'brand/formal.jpg', 'fabric_ar' => 'كريب ياباني', 'fabric_en' => 'Japanese crepe', 'description_ar' => 'عباية رسمية بقصة مستقيمة وتطريز هادئ على الكم.', 'description_en' => 'A formal straight-cut abaya with quiet sleeve embroidery.'],
            ['slug' => 'misk', 'category' => 'formal', 'name_ar' => 'عباية مسك', 'name_en' => 'Misk abaya', 'price' => 62000, 'image' => 'brand/formal.jpg', 'fabric_ar' => 'نيدا', 'fabric_en' => 'Nida', 'description_ar' => 'انسياب رسمي بلون حبري وتفاصيل نحاسية خفيفة.', 'description_en' => 'A formal drape in ink black with a light copper detail.'],
            ['slug' => 'dafa', 'category' => 'winter', 'name_ar' => 'عباية دفء', 'name_en' => 'Dafa abaya', 'price' => 70000, 'image' => 'brand/winter.jpg', 'fabric_ar' => 'جوخ خفيف', 'fabric_en' => 'Light wool blend', 'description_ar' => 'عباية شتوية بقماش أثقل وياقة تغطي الكتف.', 'description_en' => 'A winter abaya in a heavier cloth with a shoulder-covering collar.'],
            ['slug' => 'ghayma', 'category' => 'winter', 'name_ar' => 'عباية غيمة', 'name_en' => 'Ghayma abaya', 'price' => 68000, 'image' => 'brand/winter.jpg', 'fabric_ar' => 'كريب شتوي', 'fabric_en' => 'Winter crepe', 'description_ar' => 'قصّة واسعة تمنح دفئًا دون ثقل ظاهر.', 'description_en' => 'A wider cut that feels warm without looking heavy.'],
            ['slug' => 'naseem', 'category' => 'summer', 'name_ar' => 'عباية نسيم', 'name_en' => 'Naseem abaya', 'price' => 45000, 'image' => 'brand/summer.jpg', 'fabric_ar' => 'لينن مخلوط', 'fabric_en' => 'Linen blend', 'description_ar' => 'عباية صيفية خفيفة بلون رملي وتطريز ناعم.', 'description_en' => 'A light summer abaya in sand with soft embroidery.'],
            ['slug' => 'leen', 'category' => 'summer', 'name_ar' => 'عباية لين', 'name_en' => 'Leen abaya', 'price' => 48000, 'image' => 'brand/summer.jpg', 'fabric_ar' => 'شيفون مبطن', 'fabric_en' => 'Lined chiffon', 'description_ar' => 'خفة الصيف مع بطانة تحفظ شكل العباية.', 'description_en' => 'Summer lightness with a lining that keeps the shape.'],
            ['slug' => 'malika', 'category' => 'vip', 'name_ar' => 'عباية ملكة', 'name_en' => 'Malika abaya', 'price' => 120000, 'image' => 'brand/formal.jpg', 'fabric_ar' => 'حرير كريب', 'fabric_en' => 'Crepe silk', 'description_ar' => 'عباية مناسبات بتطريز أوفى وقصة مفصّلة.', 'description_en' => 'An occasion abaya with fuller embroidery and a tailored cut.'],
            ['slug' => 'aseel', 'category' => 'vip', 'name_ar' => 'عباية أصيل', 'name_en' => 'Aseel abaya', 'price' => 145000, 'image' => 'brand/winter.jpg', 'fabric_ar' => 'مخمل ناعم', 'fabric_en' => 'Soft velvet', 'description_ar' => 'حضور للمساء، بقماش أثقل وتفاصيل يدوية.', 'description_en' => 'An evening presence, in a richer cloth with hand-finished detail.'],
        ];

        foreach ($products as $row) {
            $category = Category::query()->where('slug', $row['category'])->firstOrFail();
            $product = Product::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'category_id' => $category->id,
                    'name_ar' => $row['name_ar'],
                    'name_en' => $row['name_en'],
                    'description_ar' => $row['description_ar'],
                    'description_en' => $row['description_en'],
                    'fabric_ar' => $row['fabric_ar'],
                    'fabric_en' => $row['fabric_en'],
                    'price' => $row['price'],
                    'is_active' => true,
                ],
            );

            $product->images()->delete();
            $product->images()->create(['path' => $row['image'], 'sort' => 1]);
            $product->variants()->delete();

            foreach (['52', '54', '56', '58', '60'] as $size) {
                $product->variants()->create([
                    'size' => $size,
                    'sku' => strtoupper($row['slug']).'-'.$size,
                    'stock' => 4,
                ]);
            }
        }

        $rates = [
            ['مسقط', 'Muscat', 2000],
            ['الداخلية', 'Ad Dakhiliyah', 2000],
            ['شمال الباطنة', 'North Al Batinah', 2000],
            ['جنوب الباطنة', 'South Al Batinah', 2000],
            ['جنوب الشرقية', 'South Ash Sharqiyah', 2500],
            ['شمال الشرقية', 'North Ash Sharqiyah', 2500],
            ['الظاهرة', 'Ad Dhahirah', 2500],
            ['البريمي', 'Al Buraimi', 2500],
            ['ظفار', 'Dhofar', 3000],
            ['مسندم', 'Musandam', 3500],
            ['الوسطى', 'Al Wusta', 3500],
        ];

        foreach ($rates as $index => [$ar, $en, $fee]) {
            ShippingRate::query()->updateOrCreate(
                ['governorate_en' => $en],
                ['governorate_ar' => $ar, 'fee' => $fee, 'is_active' => true, 'sort' => $index + 1],
            );
        }

        $settings = [
            'topline_ar' => 'شيزين · تفاصيل تليق بكِ',
            'topline_en' => 'SHEZAIN · Details that reflect you',
            'hero_title_ar' => "حضور هادئ.\nأناقة تُحكى.",
            'hero_title_en' => "Quiet presence.\nLasting elegance.",
            'hero_intro_ar' => "عبايات تختارينها لذوقكِ،\nوترافقكِ في كل لحظة.",
            'hero_intro_en' => "Abayas chosen for your style,\nmade for every moment.",
            'hero_button_ar' => 'اكتشفي المجموعة',
            'hero_button_en' => 'Explore the collections',
            'hero_foot_ar' => 'تفاصيل مدروسة ◆ قصّات تنساب بأناقة',
            'hero_foot_en' => 'Thoughtful details ◆ Effortless silhouettes',
            'collection_kicker_ar' => 'اختاري ما يناسب يومكِ',
            'collection_kicker_en' => 'Find your everyday elegance',
            'brand_title_ar' => 'لكلّ ذوق، شيزين.',
            'brand_title_en' => 'Your style. Your SHEZAIN.',
            'brand_text_ar' => 'من أناقة يومكِ إلى خصوصية مناسباتكِ.',
            'brand_text_en' => 'From everyday elegance to your special occasions.',
            'phone' => '96890000000',
            'email' => 'hello@shezain.om',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
