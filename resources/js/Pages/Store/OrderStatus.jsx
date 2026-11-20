import { Head, Link, router } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

export default function OrderStatus({ order, outcome }) {
    const { locale, t } = useLocale();
    const note = order.status === 'paid' || order.status === 'processing'
        ? t('paidNote')
        : outcome === 'cancel'
            ? t('cancelNote')
            : t('pendingNote');

    return (
        <StoreLayout>
            <Head title={`${t('order')} ${order.number}`} />
            <section className="narrow" style={{ maxWidth: 760 }}>
                <span className="eyebrow">{t('order')} {order.number}</span>
                <h1>{t(order.status)}</h1>
                <p className="prose">{note}</p>
                {order.items.map((item, index) => (
                    <div className="split" key={index}>
                        <span>{localized(locale, item, 'name')} · {item.size} × {item.quantity}</span>
                        <span>{item.line_total}</span>
                    </div>
                ))}
                <div className="total-row" style={{ margin: '18px 0' }}>
                    <span>{t('total')}</span>
                    <strong>{order.total} {t('price')}</strong>
                </div>
                <p>{order.governorate_ar} — {order.city}<br />{order.address_line}</p>
                {order.status === 'pending_payment' && (
                    <button className="primary" type="button" onClick={() => router.post(`/checkout/${order.public_id}/pay`)}>
                        {t('retry')}
                    </button>
                )}
                <p><Link href="/">{t('continue')}</Link></p>
            </section>
        </StoreLayout>
    );
}
