import { useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Show({ order, transitions }) {
    const { locale, t } = useLocale();
    const form = useForm({ status: transitions[0] ?? '' });

    return (
        <AdminLayout
            title={order.number}
            lede={order.created_at}
            back={{ href: '/admin/orders', label: t('backToOrders') }}
            actions={<StatusMark status={order.status} label={t(order.status)} />}
        >
            <div className="admin-split">
                <Sheet title={t('customerDetails')}>
                    <dl className="meta">
                        <dt>{t('name')}</dt>
                        <dd>{order.customer_name}</dd>
                        <dt>{t('phone')}</dt>
                        <dd className="num">{order.phone}</dd>
                        <dt>{t('email')}</dt>
                        <dd>{order.email}</dd>
                    </dl>
                </Sheet>
                <Sheet title={t('deliveryDetails')}>
                    <dl className="meta">
                        <dt>{t('governorate')}</dt>
                        <dd>{localized(locale, order, 'governorate')}</dd>
                        <dt>{t('city')}</dt>
                        <dd>{order.city}</dd>
                        <dt>{t('address')}</dt>
                        <dd>{order.address_line}</dd>
                        {order.notes && (
                            <>
                                <dt>{t('notes')}</dt>
                                <dd>{order.notes}</dd>
                            </>
                        )}
                    </dl>
                </Sheet>
            </div>
            <Sheet title={t('orderItems')}>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('products')}</th>
                                <th>{t('size')}</th>
                                <th>{t('sku')}</th>
                                <th>{t('quantity')}</th>
                                <th>{t('price')}</th>
                                <th>{t('total')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {order.items.map((item, index) => (
                                <tr key={`${item.sku ?? index}`}>
                                    <td>{localized(locale, item, 'name')}</td>
                                    <td>{item.size}</td>
                                    <td className="num">{item.sku}</td>
                                    <td className="num">{item.quantity}</td>
                                    <td className="num">{item.unit_price}</td>
                                    <td className="num">{item.line_total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="totals">
                    <span>{t('subtotal')}</span>
                    <b className="num">{order.subtotal}</b>
                    <span>{t('shippingFee')}</span>
                    <b className="num">{order.shipping_fee}</b>
                    <span>{t('total')}</span>
                    <b className="num">{order.total} {t('price')}</b>
                </div>
            </Sheet>
            <Sheet title={t('updateStatus')}>
                {transitions.length === 0 ? (
                    <p className="sheet-note">{t('noStatusChange')}</p>
                ) : (
                    <form
                        className="status-form"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.patch(`/admin/orders/${order.id}`);
                        }}
                    >
                        <label className="field">
                            <span>{t('status')}</span>
                            <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)}>
                                {transitions.map((status) => <option key={status} value={status}>{t(status)}</option>)}
                            </select>
                        </label>
                        <button className="primary" type="submit" disabled={form.processing}>{t('save')}</button>
                    </form>
                )}
            </Sheet>
        </AdminLayout>
    );
}
