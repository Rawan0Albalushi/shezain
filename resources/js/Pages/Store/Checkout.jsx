import { Head, useForm, usePage } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

export default function Checkout({ lines, subtotal, subtotal_baisa: subtotalBaisa, rates, addresses }) {
    const { auth, thawaniSandbox } = usePage().props;
    const { locale, t } = useLocale();
    const form = useForm({
        customer_name: auth.user?.name ?? '',
        phone: auth.user?.phone ?? '',
        email: auth.user?.email ?? '',
        governorate_id: rates[0]?.id ?? '',
        city: '',
        address_line: '',
        notes: '',
        save_address: false,
        create_account: false,
        password: '',
        password_confirmation: '',
    });

    const rate = rates.find((item) => item.id === Number(form.data.governorate_id));
    const total = subtotalBaisa + (rate?.fee ?? 0);
    const totalFormatted = formatBaisa(total);

    function applyAddress(event) {
        const address = addresses.find((item) => item.id === Number(event.target.value));
        if (!address) {
            return;
        }
        form.setData({
            ...form.data,
            customer_name: address.name,
            phone: address.phone,
            governorate_id: address.governorate_id,
            city: address.city,
            address_line: address.address_line,
        });
    }

    function submit(event) {
        event.preventDefault();
        form.post('/checkout');
    }

    return (
        <StoreLayout>
            <Head title={t('checkout')} />
            <section className="page">
                <h1>{t('checkout')}</h1>
                {thawaniSandbox && <p className="sample-note">{t('sandbox')}</p>}
                <div className="page-grid">
                    <form onSubmit={submit}>
                        {addresses.length > 0 && (
                            <label>
                                {t('addresses')}
                                <select defaultValue="" onChange={applyAddress}>
                                    <option value="">{t('address')}</option>
                                    {addresses.map((address) => (
                                        <option key={address.id} value={address.id}>{address.label || address.city}</option>
                                    ))}
                                </select>
                            </label>
                        )}
                        <Field label={t('name')} error={form.errors.customer_name}>
                            <input value={form.data.customer_name} onChange={(event) => form.setData('customer_name', event.target.value)} required />
                        </Field>
                        <Field label={t('phone')} error={form.errors.phone}>
                            <input value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} required />
                        </Field>
                        <Field label={t('email')} error={form.errors.email}>
                            <input type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} required />
                        </Field>
                        <Field label={t('governorate')} error={form.errors.governorate_id}>
                            <select value={form.data.governorate_id} onChange={(event) => form.setData('governorate_id', Number(event.target.value))}>
                                {rates.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {localized(locale, { governorate_ar: item.governorate_ar, governorate_en: item.governorate_en }, 'governorate')} — {item.fee_formatted}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        <Field label={t('city')} error={form.errors.city}>
                            <input value={form.data.city} onChange={(event) => form.setData('city', event.target.value)} required />
                        </Field>
                        <Field label={t('address')} error={form.errors.address_line}>
                            <textarea value={form.data.address_line} onChange={(event) => form.setData('address_line', event.target.value)} required />
                        </Field>
                        <Field label={t('notes')} error={form.errors.notes}>
                            <textarea value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                        </Field>
                        {auth.user && (
                            <label className="choice">
                                <input type="checkbox" checked={form.data.save_address} onChange={(event) => form.setData('save_address', event.target.checked)} />
                                {t('saveAddress')}
                            </label>
                        )}
                        {!auth.user && (
                            <>
                                <label className="choice">
                                    <input type="checkbox" checked={form.data.create_account} onChange={(event) => form.setData('create_account', event.target.checked)} />
                                    {t('createAccount')}
                                </label>
                                {form.data.create_account && (
                                    <>
                                        <Field label={t('password')} error={form.errors.password}>
                                            <input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} />
                                        </Field>
                                        <Field label={t('confirmPassword')}>
                                            <input type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} />
                                        </Field>
                                    </>
                                )}
                            </>
                        )}
                        {form.errors.cart && <p className="error-text">{form.errors.cart}</p>}
                        <button className="primary" disabled={form.processing} type="submit">{t('pay')}</button>
                    </form>
                    <aside className="summary">
                        {lines.map((line) => (
                            <div className="split" key={line.variant_id} style={{ marginBottom: 12 }}>
                                <span>{localized(locale, line, 'name')} · {line.size} × {line.quantity}</span>
                                <span>{line.line_total_formatted}</span>
                            </div>
                        ))}
                        <div className="split"><span>{t('subtotal')}</span><span>{subtotal}</span></div>
                        <div className="split"><span>{t('shippingFee')}</span><span>{rate?.fee_formatted ?? '0.000'}</span></div>
                        <div className="total-row" style={{ marginTop: 16 }}>
                            <strong>{t('total')}</strong>
                            <strong>{totalFormatted} {t('price')}</strong>
                        </div>
                    </aside>
                </div>
            </section>
        </StoreLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="field">
            <span>{label}</span>
            {children}
            {error && <span className="error-text">{error}</span>}
        </label>
    );
}

function formatBaisa(baisa) {
    const sign = baisa < 0 ? '-' : '';
    const value = Math.abs(baisa);

    return `${sign}${Math.floor(value / 1000)}.${String(value % 1000).padStart(3, '0')}`;
}
