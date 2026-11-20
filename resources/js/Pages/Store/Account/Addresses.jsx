import { Head, router, useForm } from '@inertiajs/react';
import StoreLayout from '../../../Components/StoreLayout';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Addresses({ addresses, rates }) {
    const { locale, t } = useLocale();
    const form = useForm({
        label: '',
        name: '',
        phone: '',
        shipping_rate_id: rates[0]?.id ?? '',
        city: '',
        address_line: '',
        is_default: false,
    });

    return (
        <StoreLayout>
            <Head title={t('addresses')} />
            <section className="page">
                <h1>{t('addresses')}</h1>
                <div className="page-grid">
                    <div>
                        {addresses.map((address) => (
                            <article key={address.id} className="summary" style={{ marginBottom: 12 }}>
                                <strong>{address.label || address.name}</strong>
                                <p>{address.name} · {address.phone}</p>
                                <p>{localized(locale, address, 'governorate')} — {address.city}</p>
                                <p>{address.address_line}</p>
                                <button type="button" className="text-button" onClick={() => router.delete(`/account/addresses/${address.id}`)}>{t('remove')}</button>
                            </article>
                        ))}
                    </div>
                    <form onSubmit={(event) => { event.preventDefault(); form.post('/account/addresses'); }}>
                        <label className="field"><span>{t('label')}</span><input value={form.data.label} onChange={(event) => form.setData('label', event.target.value)} /></label>
                        <label className="field"><span>{t('name')}</span><input value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} required /></label>
                        <label className="field"><span>{t('phone')}</span><input value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} required /></label>
                        <label className="field">
                            <span>{t('governorate')}</span>
                            <select value={form.data.shipping_rate_id} onChange={(event) => form.setData('shipping_rate_id', Number(event.target.value))}>
                                {rates.map((rate) => <option key={rate.id} value={rate.id}>{localized(locale, rate, 'governorate')}</option>)}
                            </select>
                        </label>
                        <label className="field"><span>{t('city')}</span><input value={form.data.city} onChange={(event) => form.setData('city', event.target.value)} required /></label>
                        <label className="field"><span>{t('address')}</span><textarea value={form.data.address_line} onChange={(event) => form.setData('address_line', event.target.value)} required /></label>
                        <label className="choice">
                            <input type="checkbox" checked={form.data.is_default} onChange={(event) => form.setData('is_default', event.target.checked)} />
                            {t('defaultAddress')}
                        </label>
                        <button className="primary" type="submit">{t('save')}</button>
                    </form>
                </div>
            </section>
        </StoreLayout>
    );
}
