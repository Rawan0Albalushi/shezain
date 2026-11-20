import { Head, Link } from '@inertiajs/react';
import Pager from '../../../Components/Pager';
import StoreLayout from '../../../Components/StoreLayout';
import { useLocale } from '../../../hooks';

export default function Orders({ orders }) {
    const { t } = useLocale();

    return (
        <StoreLayout>
            <Head title={t('orders')} />
            <section className="narrow">
                <h1>{t('orders')}</h1>
                <p><Link href="/account/addresses">{t('addresses')}</Link></p>
                {orders.data.length === 0 ? <p>{t('noOrders')}</p> : (
                    <table>
                        <thead>
                            <tr>
                                <th>{t('order')}</th>
                                <th>{t('status')}</th>
                                <th>{t('total')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.map((order) => (
                                <tr key={order.id}>
                                    <td><Link href={`/account/orders/${order.id}`}>{order.number}</Link></td>
                                    <td>{t(order.status)}</td>
                                    <td>{order.total} {t('price')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
                <Pager paginator={orders} />
            </section>
        </StoreLayout>
    );
}
