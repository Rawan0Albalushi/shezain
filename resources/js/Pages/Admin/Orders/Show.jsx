import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

const fulfillment = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered'];

export default function Show({ order, transitions }) {
    const { locale, t } = useLocale();
    const form = useForm({ status: '' });
    const [pendingStatus, setPendingStatus] = useState(null);
    const currentIndex = fulfillment.indexOf(order.status);
    const forward = transitions.filter((status) => status !== 'cancelled');
    const canCancel = transitions.includes('cancelled');

    function hint(status) {
        if (status === 'cancelled') {
            return t(order.status === 'pending_payment' ? 'statusHint_cancel_hold' : 'statusHint_cancel_paid');
        }

        return t(`statusHint_${status}`);
    }

    function confirmText(status) {
        const mark = locale === 'ar' ? '؟' : '?';

        return `${t('confirmStatusTo')} ${t(status)}${mark}`;
    }

    function submit(status) {
        form.clearErrors();
        form.transform(() => ({ status }));
        form.patch(`/admin/orders/${order.id}`, {
            onFinish: () => setPendingStatus(null),
        });
    }

    function StatusAction({ status, tone }) {
        const confirming = pendingStatus === status;
        const buttonClass = tone === 'cancel' ? 'status-cancel' : 'primary';

        return (
            <div className="status-action">
                {confirming ? (
                    <>
                        <p className={tone === 'cancel' ? 'status-warn' : 'status-ask'}>{confirmText(status)}</p>
                        <div className="status-confirm">
                            <button type="button" className={buttonClass} disabled={form.processing} onClick={() => submit(status)}>
                                {t('confirmStatus')}
                            </button>
                            <button type="button" className="status-dismiss" disabled={form.processing} onClick={() => setPendingStatus(null)}>
                                {t('back')}
                            </button>
                        </div>
                        <p className="sub">{hint(status)}</p>
                    </>
                ) : (
                    <>
                        <button type="button" className={buttonClass} disabled={form.processing} onClick={() => setPendingStatus(status)}>
                            {t(`statusAction_${status}`)}
                        </button>
                        <p className="sub">{hint(status)}</p>
                    </>
                )}
            </div>
        );
    }

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
                                <th>{t('color')}</th>
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
                                    <td>
                                        <span className="order-line">
                                            {item.image && (
                                                <span className="order-line-photo">
                                                    <img src={item.image} alt="" />
                                                </span>
                                            )}
                                            <span>{localized(locale, item, 'name')}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <span className="color-cell">
                                            <span className="swatch" style={item.swatch ? { background: item.swatch } : undefined} />
                                            {localized(locale, item, 'color')}
                                        </span>
                                    </td>
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
                <div className="status-panel">
                    {currentIndex >= 0 && (
                        <ol className="status-path" aria-label={t('statusPath')}>
                            {fulfillment.map((status, index) => {
                                const state = index < currentIndex ? 'is-done' : index === currentIndex ? 'is-current' : 'is-ahead';

                                return (
                                    <li key={status} className={state} aria-current={index === currentIndex ? 'step' : undefined}>
                                        <span className="status-path-mark" aria-hidden="true" />
                                        <span>{t(status)}</span>
                                    </li>
                                );
                            })}
                        </ol>
                    )}
                    <p className="status-now">{t(`statusNow_${order.status}`)}</p>
                    {(forward.length > 0 || canCancel) && (
                        <div className="status-actions">
                            {forward.map((status) => (
                                <StatusAction key={status} status={status} tone="forward" />
                            ))}
                            {canCancel && <StatusAction status="cancelled" tone="cancel" />}
                        </div>
                    )}
                    {form.errors.status && <p className="error-text" role="alert">{form.errors.status}</p>}
                </div>
            </Sheet>
        </AdminLayout>
    );
}
