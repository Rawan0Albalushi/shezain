import { Link } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { EyeIcon } from '../../Components/AdminIcons';
import { IconButton, Ledger, Sheet, StatusMark } from '../../Components/AdminUi';
import { useLocale } from '../../hooks';

export default function Dashboard({ stats, orders, today }) {
    const { t } = useLocale();

    return (
        <AdminLayout title={t('dashboard')} lede={t('dashboardIntro')}>
            <Ledger
                items={[
                    { key: 'sales', value: `${stats.paid_total} ${t('price')}`, label: t('salesConfirmed'), href: '/admin/orders' },
                    { key: 'today', value: stats.today, label: t('todayOrders'), href: `/admin/orders?from=${today}&to=${today}` },
                    { key: 'pending', value: stats.pending, label: t('pending_payment'), href: '/admin/orders?status=pending_payment' },
                    { key: 'fulfill', value: stats.to_fulfill, label: t('toFulfill'), href: '/admin/orders?status=fulfill' },
                    { key: 'shipped', value: stats.shipped, label: t('shipped'), href: '/admin/orders?status=shipped' },
                    { key: 'delivered', value: stats.delivered, label: t('delivered'), href: '/admin/orders?status=delivered' },
                    { key: 'unread', value: stats.unread, label: t('unread'), href: '/admin/messages?state=unread' },
                    { key: 'low', value: stats.low_stock, label: t('lowStock'), href: '/admin/products?stock=low' },
                    { key: 'out', value: stats.out_of_stock, label: t('outOfStock'), href: '/admin/products?stock=out' },
                ]}
            />
            <Sheet
                title={t('recentOrders')}
                detail={t('recentOrdersDetail')}
                action={<Link className="quiet" href="/admin/orders">{t('viewAll')}</Link>}
            >
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('order')}</th>
                                <th>{t('customer')}</th>
                                <th>{t('city')}</th>
                                <th>{t('status')}</th>
                                <th>{t('total')}</th>
                                <th>{t('when')}</th>
                                <th>{t('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.length === 0 && (
                                <tr>
                                    <td className="empty-row" colSpan={7}>{t('noOrders')}</td>
                                </tr>
                            )}
                            {orders.map((order) => (
                                <tr key={order.id}>
                                    <td><Link href={`/admin/orders/${order.id}`}>{order.number}</Link></td>
                                    <td>{order.customer_name}<div className="sub">{order.phone}</div></td>
                                    <td>{order.city}</td>
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
