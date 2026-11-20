import { Link } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Components/AdminLayout';
import { EyeIcon } from '../../../Components/AdminIcons';
import { applyQuery, IconButton, Ledger, Sheet, StatusMark } from '../../../Components/AdminUi';
import Pager from '../../../Components/Pager';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

const statuses = ['', 'fulfill', 'pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'payment_failed'];

export default function Index({ orders, filters, counts }) {
    const { locale, t } = useLocale();
    const [q, setQ] = useState(filters.q ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    function apply(patch) {
        applyQuery('/admin/orders', { ...filters, q, from, to }, patch);
    }

    function statusLabel(item) {
        if (item === '') {
            return t('every');
        }

        if (item === 'fulfill') {
            return t('toFulfill');
        }

        return t(item);
    }

    const chips = [
        ['all', ''],
        ['fulfill', 'toFulfill'],
        ['pending_payment', 'pending_payment'],
        ['paid', 'paid'],
        ['processing', 'processing'],
        ['shipped', 'shipped'],
        ['delivered', 'delivered'],
        ['cancelled', 'cancelled'],
        ['payment_failed', 'payment_failed'],
    ];

    return (
        <AdminLayout title={t('adminOrders')} lede={t('ordersIntro')}>
            <Ledger
                items={chips.map(([key, label]) => ({
                    key,
                    value: counts[key] ?? 0,
                    label: key === 'all' ? t('allOrders') : t(label),
                    href: key === 'all' ? '/admin/orders' : `/admin/orders?status=${key}`,
                    current: (filters.status || '') === (key === 'all' ? '' : key),
                }))}
            />
            <form
                className="toolbar"
                onSubmit={(event) => {
                    event.preventDefault();
                    apply({ q, from, to });
                }}
            >
                <label className="field field-search">
                    <span>{t('find')}</span>
                    <input value={q} onChange={(event) => setQ(event.target.value)} placeholder={t('searchOrders')} />
                </label>
                <label className="field">
                    <span>{t('status')}</span>
                    <select value={filters.status ?? ''} onChange={(event) => apply({ status: event.target.value })}>
                        {statuses.map((item) => (
                            <option key={item || 'all'} value={item}>{statusLabel(item)}</option>
                        ))}
                    </select>
                </label>
                <label className="field">
                    <span>{t('from')}</span>
                    <input type="date" value={from} onChange={(event) => setFrom(event.target.value)} />
                </label>
                <label className="field">
                    <span>{t('to')}</span>
                    <input type="date" value={to} onChange={(event) => setTo(event.target.value)} />
                </label>
                <div className="toolbar-actions">
                    <button className="primary" type="submit">{t('apply')}</button>
                    <Link className="quiet" href="/admin/orders">{t('clearFilters')}</Link>
                </div>
            </form>
            <Sheet title={t('adminOrders')} detail={`${orders.total} ${t('results')}`}>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('order')}</th>
                                <th>{t('customer')}</th>
                                <th>{t('delivery')}</th>
                                <th>{t('status')}</th>
                                <th>{t('total')}</th>
                                <th>{t('when')}</th>
                                <th>{t('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.length === 0 && (
                                <tr>
                                    <td className="empty-row" colSpan={7}>{t('noMatches')}</td>
                                </tr>
                            )}
                            {orders.data.map((order) => (
                                <tr key={order.id}>
                                    <td>
                                        <Link href={`/admin/orders/${order.id}`}>{order.number}</Link>
                                        <div className="sub">{order.items_count} {t('pieces')}</div>
                                    </td>
                                    <td>
                                        {order.customer_name}
                                        <div className="sub">{order.phone}</div>
                                    </td>
                                    <td>
                                        {localized(locale, order, 'governorate')}
                                        <div className="sub">{order.city}</div>
                                    </td>
                                    <td><StatusMark status={order.status} label={t(order.status)} /></td>
                                    <td className="num">{order.total}</td>
                                    <td className="num">{order.created_at}</td>
                                    <td className="actions-cell">
                                        <IconButton label={t('viewOrder')} href={`/admin/orders/${order.id}`}>
                                            <EyeIcon />
                                        </IconButton>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Sheet>
            <Pager paginator={orders} />
        </AdminLayout>
    );
}
