<?php

namespace App\Support;

final class SitePages
{
    /**
     * @return array<string, array<string, string>>|null
     */
    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        return [
            'about' => [
                'title_ar' => 'عن شيزين',
                'title_en' => 'About SHEZAIN',
                'body_ar' => 'شيزين بيت عبايات عُماني. نختار القصّة والقماش والتفاصيل بهدوء، لتكون العباية حاضرة دون ضجيج. المجموعة الأولى تُعرض هنا، والشحن داخل سلطنة عُمان.',
                'body_en' => 'SHEZAIN is an Omani abaya house. Cut, fabric and detail are chosen quietly, so the abaya is present without noise. The first collection is shown here, with delivery across the Sultanate of Oman.',
            ],
            'shipping' => [
                'title_ar' => 'الشحن والإرجاع',
                'title_en' => 'Shipping and returns',
                'body_ar' => 'الشحن داخل سلطنة عُمان فقط، وتختلف الأجرة حسب المحافظة. يُجهّز الطلب بعد تأكيد ثواني للدفع. الإرجاع خلال 7 أيام من الاستلام إذا كانت العباية بحالتها الأصلية وغير مستخدمة، بعد مراجعة المقاس مع شيزين.',
                'body_en' => 'Delivery is within the Sultanate of Oman, and the fee depends on the governorate. An order is prepared after Thawani confirms payment. Returns are accepted within 7 days of delivery when the abaya is unused and in its original condition, after SHEZAIN reviews the size.',
            ],
            'sizes' => [
                'title_ar' => 'دليل المقاسات',
                'title_en' => 'Size guide',
                'body_ar' => 'مقاسات شيزين بالأرقام: 52، 54، 56، 58، 60. المقاس يقارب الطول والانسياب المعتاد للعباية الخليجية. إذا كنتِ بين مقاسين، اختاري الأكبر لراحة الحركة. يمكن كتابة طولكِ في ملاحظات الطلب لنراجع المقاس قبل التجهيز.',
                'body_en' => 'SHEZAIN sizes are numbered 52, 54, 56, 58 and 60, close to the usual Gulf abaya length and drape. Between two sizes, choose the larger one for ease of movement. Add your height in the order notes and we will review the size before packing.',
            ],
            'privacy' => [
                'title_ar' => 'الخصوصية',
                'title_en' => 'Privacy',
                'body_ar' => 'نستخدم الاسم والجوال والبريد والعنوان لإتمام الطلب والشحن والتواصل بشأنه. الدفع يتم على صفحة ثواني، ولا تصلنا بيانات البطاقة. لا نبيع هذه البيانات. يمكنك طلب حذف حسابكِ بمراسلتنا من صفحة التواصل.',
                'body_en' => 'We use your name, phone, email and address to complete the order, ship it, and contact you about it. Payment happens on Thawani’s page, and card details never reach us. We do not sell this information. You can ask us to delete your account from the contact page.',
            ],
        ];
    }
}
