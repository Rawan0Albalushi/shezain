import { useForm } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { Ledger, Sheet } from '../../Components/AdminUi';
import { useLocale } from '../../hooks';

export default function Shipping({ rates, totals }) {
    const { t } = useLocale();
    const form = useForm({
        rates: rates.map((rate) => ({ id: rate.id, fee: rate.fee, is_active: rate.is_active, governorate_ar: rate.governorate_ar, governorate_en: rate.governorate_en })),
    });

    function update(index, field, value) {
        form.setData('rates', form.data.rates.map((rate, rateIndex) => (rateIndex === index ? { ...rate, [field]: value } : rate)));
    }

    return (
        <AdminLayout title={t('shippingAdmin')} lede={t('shippingIntro')}>
            <Ledger
                items={[
                    { key: 'count', value: totals.count, label: t('governorates') },
                    { key: 'active', value: totals.active, label: t('activeRates') },
                    { key: 'paused', value: totals.paused, label: t('pausedRates') },
                    { key: 'highest', value: `${totals.highest} ${t('price')}`, label: t('highestFee') },
                ]}
            />
            <form onSubmit={(event) => { event.preventDefault(); form.put('/admin/shipping'); }}>
                <Sheet
                    title={t('shippingAdmin')}
                    detail={t('shippingSheet')}
                    action={<button className="primary" type="submit" disabled={form.processing}>{t('save')}</button>}
                >
                    <div className="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>{t('governorate')}</th>
                                    <th>{t('shippingFee')}</th>
                                    <th>{t('appearance')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {form.data.rates.length === 0 && (
                                    <tr>
                                        <td className="empty-row" colSpan={3}>{t('noMatches')}</td>
                                    </tr>
                                )}
                                {form.data.rates.map((rate, index) => (
                                    <tr key={rate.id}>
                                        <td>
                                            {rate.governorate_ar}
                                            <div className="sub">{rate.governorate_en}</div>
                                        </td>
                                        <td>
                                            <label className="field fee-field">
                                                <span className="sr-only">{t('shippingFee')} {rate.governorate_ar}</span>
                                                <input inputMode="decimal" value={rate.fee} onChange={(event) => update(index, 'fee', event.target.value)} />
                                            </label>
                                        </td>
                                        <td>
                                            <label className="choice">
                                                <input type="checkbox" checked={rate.is_active} onChange={(event) => update(index, 'is_active', event.target.checked)} />
                                                {t('active')}
                                            </label>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {form.errors.rates && <p className="error-text">{form.errors.rates}</p>}
                </Sheet>
            </form>
        </AdminLayout>
    );
}
