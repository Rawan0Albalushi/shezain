import { Head } from '@inertiajs/react';
import StoreLayout from '../../../Components/StoreLayout';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Order({ order }) {
    const { locale, t } = useLocale();

    return (
        <StoreLayout>
            <Head title={order.number} />
            <section className="narrow" style={{ maxWidth: 760 }}>
                <h1>{order.number}</h1>
                <p>{t(order.status)} · {order.created_at}</p>
                {order.items.map((item, index) => (
                    <div className="split" key={index}>
                        <span>{localized(locale, item, 'name')} · {item.size} × {item.quantity}</span>
                        <span>{item.line_total} {t('price')}</span>
                    </div>
                ))}
                <div className="total-row"><strong>{t('total')}</strong><strong>{order.total} {t('price')}</strong></div>
                <p>{order.address_line}، {order.city}</p>
            </section>
        </StoreLayout>
    );
}
