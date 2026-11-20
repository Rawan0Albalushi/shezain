import { useEffect, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

const fulfillment = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered'];

function StepMark({ done }) {
    return (
        <span className="status-path-mark" aria-hidden="true">
            {done && (
                <svg viewBox="0 0 16 16" width="12" height="12">
                    <path d="M3.2 8.2 6.4 11.4 12.8 4.6" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            )}
        </span>
    );
}

export default function Show({ order, transitions }) {
    const { locale, t } = useLocale();
    const form = useForm({ status: '' });
    const dialogRef = useRef(null);
    const [pendingStatus, setPendingStatus] = useState(null);
    const currentIndex = fulfillment.indexOf(order.status);
    const forward = transitions.filter((status) => status !== 'cancelled');
    const canCancel = transitions.includes('cancelled');
    const canUpdate = forward.length > 0 || canCancel;

    function hint(status) {
        if (status === 'cancelled') {
            return t(order.status === 'pending_payment' ? 'statusHint_cancel_hold' : 'statusHint_cancel_paid');
        }

        return t(`statusHint_${status}`);
    }

    function ask(status) {
        form.clearErrors();
        setPendingStatus(status);
    }

    function dismiss() {
        if (form.processing) {
            return;
        }

        dialogRef.current?.close();
    }

    function submit(status) {
        form.clearErrors();
        form.transform(() => ({ status }));
        form.patch(`/admin/orders/${order.id}`, {
            preserveScroll: true,
            onSuccess: () => dialogRef.current?.close(),
        });
    }

    useEffect(() => {
        const dialog = dialogRef.current;

        if (!dialog || !pendingStatus) {
            return;
        }

        if (!dialog.open) {
            dialog.showModal();
        }

        dialog.querySelector('[data-confirm]')?.focus();
    }, [pendingStatus]);

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
                    <table className="order-items">
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
                                        <StepMark done={index < currentIndex} />
                                        <span className="status-path-label">{t(status)}</span>
                                    </li>
                                );
                            })}
                        </ol>
                    )}
                    {canUpdate && (
                        <div className="status-next">
                            <div className="status-actions">
                                {forward.map((status) => (
                                    <button key={status} type="button" className="primary" disabled={form.processing} onClick={() => ask(status)}>
                                        {t(`statusAction_${status}`)}
                                    </button>
                                ))}
                                {canCancel && (
                                    <button type="button" className="status-cancel" disabled={form.processing} onClick={() => ask('cancelled')}>
                                        {t('statusAction_cancelled')}
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </Sheet>
            <dialog
                ref={dialogRef}
                className="status-dialog"
                aria-labelledby="status-dialog-title"
                aria-describedby="status-dialog-hint"
                onClick={(event) => {
                    if (event.target === dialogRef.current) {
                        dismiss();
                    }
                }}
                onCancel={(event) => {
                    if (form.processing) {
                        event.preventDefault();
                    }
                }}
                onClose={() => {
                    setPendingStatus(null);
                    form.clearErrors();
                }}
            >
                {pendingStatus && (
                    <div className="status-dialog-body">
                        <h2 id="status-dialog-title">{t(`statusAction_${pendingStatus}`)}</h2>
                        <div className="status-dialog-copy">
                            <p className="status-shift" aria-hidden="true">
                                <span>{t(order.status)}</span>
                                <svg className="status-shift-arrow" viewBox="0 0 20 20" width="18" height="18">
                                    <path d="M4 10h12M12 6l4 4-4 4" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                                <span className={pendingStatus === 'cancelled' ? 'is-cancel' : 'is-next'}>{t(pendingStatus)}</span>
                            </p>
                            <p id="status-dialog-hint" className={pendingStatus === 'cancelled' ? 'status-warn' : 'status-now'}>{hint(pendingStatus)}</p>
                            {form.errors.status && <p className="error-text" role="alert">{form.errors.status}</p>}
                        </div>
                        <div className="status-dialog-actions">
                            <button
                                type="button"
                                data-confirm
                                className={pendingStatus === 'cancelled' ? 'status-cancel' : 'primary'}
                                disabled={form.processing}
                                onClick={() => submit(pendingStatus)}
                            >
                                {t('confirmStatus')}
                            </button>
                            <button type="button" className="status-dismiss" disabled={form.processing} onClick={dismiss}>
                                {t('back')}
                            </button>
                        </div>
                    </div>
                )}
            </dialog>
        </AdminLayout>
    );
}
