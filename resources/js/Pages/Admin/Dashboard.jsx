import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { useLocale } from '../../hooks';

export default function Dashboard({ stats, orders }) {
    const { t } = useLocale();

    return (
        <AdminLayout title={t('dashboard')}>
            <Head title={t('dashboard')} />
            <div className="stats">
                <div className="stat"><span>{t('total')}</span><b>{stats.paid_total}</b></div>
                <div className="stat"><span>{t('pending_payment')}</span><b>{stats.pending}</b></div>
                <div className="stat"><span>{t('toFulfill')}</span><b>{stats.to_fulfill}</b></div>
                <div className="stat"><span>{t('unread')}</span><b>{stats.unread}</b></div>
            </div>
            <table>
                <tbody>
                    {orders.map((order) => (
                        <tr key={order.id}>
                            <td><Link href={`/admin/orders/${order.id}`}>{order.number}</Link></td>
                            <td>{order.customer_name}</td>
                            <td>{t(order.status)}</td>
                            <td>{order.total}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </AdminLayout>
    );
}
