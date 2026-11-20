import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import AdminLayout from '../../Components/AdminLayout';
import { EyeIcon } from '../../Components/AdminIcons';
import { IconButton, Ledger, Sheet, StatusMark } from '../../Components/AdminUi';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

const periods = [
    ['today', 'periodToday'],
    ['week', 'periodWeek'],
    ['month', 'periodMonth'],
    ['year', 'periodYear'],
    ['all', 'periodAll'],
];

export default function Reports({ filters, report }) {
    const { locale, t } = useLocale();
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const summary = report.summary;
    const ordersHref = filters.from
        ? `/admin/orders?from=${filters.from}&to=${filters.to}`
        : '/admin/orders';

    useEffect(() => {
        setFrom(filters.from ?? '');
        setTo(filters.to ?? '');
    }, [filters.from, filters.to]);

    function apply(event) {
        event.preventDefault();

        if (!from || !to) {
            return;
        }

        router.get('/admin/reports', { period: 'custom', from, to }, { preserveScroll: true, replace: true });
    }

    return (
        <AdminLayout title={t('reportsTitle')} lede={t('reportsIntro')}>
            <form className="toolbar" onSubmit={apply}>
                <div className="period-row" role="navigation" aria-label={t('reports')}>
                    {periods.map(([key, label]) => (
                        <Link
                            key={key}
                            href={`/admin/reports?period=${key}`}
                            className={filters.period === key ? 'is-current' : undefined}
                            aria-current={filters.period === key ? 'page' : undefined}
                            preserveScroll
                        >
                            {t(label)}
                        </Link>
                    ))}
                </div>
                <label className="field">
                    <span>{t('from')}</span>
                    <input type="date" value={from} onChange={(event) => setFrom(event.target.value)} />
                </label>
                <label className="field">
                    <span>{t('to')}</span>
                    <input type="date" value={to} onChange={(event) => setTo(event.target.value)} />
                </label>
                <div className="toolbar-actions">
                    <button className="primary" type="submit" disabled={!from || !to}>{t('apply')}</button>
                    <Link className="quiet" href="/admin/reports">{t('clearFilters')}</Link>
                </div>
            </form>
            <Ledger
                items={[
                    { key: 'sales', value: `${summary.total} ${t('price')}`, label: t('salesConfirmed') },
                    { key: 'goods', value: `${summary.subtotal} ${t('price')}`, label: t('merchandise') },
                    { key: 'shipping', value: `${summary.shipping} ${t('price')}`, label: t('shippingCollected') },
                    { key: 'orders', value: summary.orders, label: t('confirmedOrders') },
                    { key: 'average', value: `${summary.average} ${t('price')}`, label: t('averageOrder') },
                    {
                        key: 'awaiting',
                        value: `${summary.awaiting_total} ${t('price')}`,
                        label: t('awaitingAmount'),
                        href: filters.from
                            ? `/admin/orders?status=pending_payment&from=${filters.from}&to=${filters.to}`
                            : '/admin/orders?status=pending_payment',
                    },
                ]}
            />
            <Sheet
                title={report.grain === 'month' ? t('monthlySales') : t('dailySales')}
                detail={filters.period === 'all' ? t('periodAll') : `${filters.from} — ${filters.to}`}
            >
                {report.series.length === 0 ? (
                    <p className="sheet-note">{t('noSalesPeriod')}</p>
                ) : (
                    <div className="report-bars">
                        {report.series.map((point) => (
                            <div className="report-bar" key={point.key}>
                                <span className="report-bar-label">{point.label}</span>
                                <div className="report-track" aria-hidden="true">
                                    <div className="report-fill" style={{ width: `${point.share}%` }} />
                                </div>
                                <span className="report-bar-value num">
                                    {point.total}
                                    <span className="sub">{point.orders} {t('ordersShort')}</span>
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </Sheet>
            <div className="admin-split">
                <Sheet title={t('byGovernorate')}>
                    <div className="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>{t('delivery')}</th>
                                    <th>{t('confirmedOrders')}</th>
                                    <th>{t('total')}</th>
                                    <th>{t('share')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.governorates.length === 0 && (
                                    <tr>
                                        <td className="empty-row" colSpan={4}>{t('noSalesPeriod')}</td>
                                    </tr>
                                )}
                                {report.governorates.map((row) => (
                                    <tr key={`${row.name_ar}-${row.name_en}`}>
                                        <td>{localized(locale, row, 'name')}</td>
                                        <td className="num">{row.orders}</td>
                                        <td className="num">{row.total}</td>
                                        <td className="num">{row.share}%</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Sheet>
                <Sheet title={t('byProduct')}>
                    <div className="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>{t('products')}</th>
                                    <th>{t('pieces')}</th>
                                    <th>{t('total')}</th>
                                    <th>{t('share')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {report.products.length === 0 && (
                                    <tr>
                                        <td className="empty-row" colSpan={4}>{t('noSalesPeriod')}</td>
                                    </tr>
                                )}
                                {report.products.map((row) => (
                                    <tr key={`${row.name_ar}-${row.name_en}`}>
                                        <td>{localized(locale, row, 'name')}</td>
                                        <td className="num">{row.quantity}</td>
                                        <td className="num">{row.total}</td>
                                        <td className="num">{row.share}%</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Sheet>
            </div>
            <Sheet
                title={t('confirmedOrders')}
                detail={`${summary.orders} ${t('results')}`}
                action={<Link className="quiet" href={ordersHref}>{t('viewAll')}</Link>}
            >
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('order')}</th>
                                <th>{t('customer')}</th>
                                <th>{t('status')}</th>
                                <th>{t('total')}</th>
                                <th>{t('when')}</th>
                                <th>{t('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.orders.length === 0 && (
                                <tr>
                                    <td className="empty-row" colSpan={6}>{t('noSalesPeriod')}</td>
                                </tr>
                            )}
                            {report.orders.map((order) => (
                                <tr key={order.id}>
                                    <td><Link href={`/admin/orders/${order.id}`}>{order.number}</Link></td>
                                    <td>{order.customer_name}</td>
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
        </AdminLayout>
    );
}
