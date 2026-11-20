import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { useLocale } from '../../hooks';

export default function Shipping({ rates }) {
    const { t } = useLocale();
    const form = useForm({
        rates: rates.map((rate) => ({ id: rate.id, fee: rate.fee, is_active: rate.is_active, governorate_ar: rate.governorate_ar, governorate_en: rate.governorate_en })),
    });

    function update(index, field, value) {
        form.setData('rates', form.data.rates.map((rate, rateIndex) => (rateIndex === index ? { ...rate, [field]: value } : rate)));
    }

    return (
        <AdminLayout title={t('shippingAdmin')}>
            <Head title={t('shippingAdmin')} />
            <form onSubmit={(event) => { event.preventDefault(); form.put('/admin/shipping'); }}>
                {form.data.rates.map((rate, index) => (
                    <div className="size-row" key={rate.id}>
                        <strong>{rate.governorate_ar}</strong>
                        <input value={rate.fee} onChange={(event) => update(index, 'fee', event.target.value)} />
                        <label className="choice">
                            <input type="checkbox" checked={rate.is_active} onChange={(event) => update(index, 'is_active', event.target.checked)} />
                            {t('active')}
                        </label>
                    </div>
                ))}
                <button className="primary" type="submit">{t('save')}</button>
            </form>
        </AdminLayout>
    );
}
