import { Head, router } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Show({ order, transitions }) {
    const { locale, t } = useLocale();

    return (
        <AdminLayout title={order.number}>
            <Head title={order.number} />
            <p>{order.customer_name} · {order.phone} · {order.email}</p>
            <p>{t(order.status)}</p>
            <p>{localized(locale, order, 'governorate')} — {order.city}<br />{order.address_line}</p>
            {order.notes && <p>{order.notes}</p>}
            {order.items.map((item, index) => (
                <div className="split" key={`${item.sku ?? index}`}>
                    <span>{localized(locale, item, 'name')} · {item.size} × {item.quantity}</span>
                    <span>{item.line_total}</span>
                </div>
            ))}
            <div className="total-row">
                <span>{t('shippingFee')} {order.shipping_fee}</span>
                <strong>{order.total} {t('price')}</strong>
            </div>
            {transitions.length > 0 && (
                <form
                    style={{ marginTop: 24 }}
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.patch(`/admin/orders/${order.id}`, {
                            status: new FormData(event.currentTarget).get('status'),
                        });
                    }}
                >
                    <label className="field">
                        <span>{t('updateStatus')}</span>
                        <select name="status" defaultValue={transitions[0]}>
                            {transitions.map((status) => <option key={status} value={status}>{t(status)}</option>)}
                        </select>
                    </label>
                    <button className="primary" type="submit">{t('save')}</button>
                </form>
            )}
        </AdminLayout>
    );
}
