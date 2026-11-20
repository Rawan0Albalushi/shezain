import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import Pager from '../../../Components/Pager';
import { useLocale } from '../../../hooks';

const statuses = ['', 'pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'payment_failed'];

export default function Index({ orders, status }) {
    const { t } = useLocale();

    return (
        <AdminLayout title={t('orders')}>
            <Head title={t('orders')} />
            <label className="field" style={{ maxWidth: 280 }}>
                <span>{t('status')}</span>
                <select value={status} onChange={(event) => router.get('/admin/orders', { status: event.target.value })}>
                    {statuses.map((item) => <option key={item} value={item}>{item === '' ? t('all') : t(item)}</option>)}
                </select>
            </label>
            <table>
                <tbody>
                    {orders.data.map((order) => (
                        <tr key={order.id}>
                            <td><Link href={`/admin/orders/${order.id}`}>{order.number}</Link></td>
                            <td>{order.customer_name}<div className="muted">{order.phone}</div></td>
                            <td>{t(order.status)}</td>
                            <td>{order.total}</td>
                            <td>{order.created_at}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <Pager paginator={orders} />
        </AdminLayout>
    );
}
