import { Head, Link, router } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

export default function Cart({ lines, subtotal }) {
    const { locale, t } = useLocale();

    return (
        <StoreLayout>
            <Head title={t('bag')} />
            <section className="narrow" style={{ maxWidth: 860 }}>
                <h1>{t('bag')}</h1>
                {lines.length === 0 ? (
                    <>
                        <p>{t('emptyBag')}</p>
                        <Link className="primary" href="/">{t('continue')}</Link>
                    </>
                ) : (
                    <>
                        {lines.map((line) => (
                            <div className="cart-row" key={line.variant_id}>
                                {line.image && <img src={line.image} alt="" />}
                                <div>
                                    <h3>{localized(locale, line, 'name')}</h3>
                                    <p>{t('size')} {line.size} · {line.unit_price_formatted} {t('price')}</p>
                                    <label>
                                        {t('quantity')}
                                        <input
                                            type="number"
                                            min="1"
                                            max={Math.min(10, line.stock)}
                                            value={line.quantity}
                                            onChange={(event) => router.patch(`/cart/${line.variant_id}`, { quantity: Number(event.target.value) }, { preserveScroll: true })}
                                        />
                                    </label>
                                    <button type="button" className="text-button" onClick={() => router.delete(`/cart/${line.variant_id}`, { preserveScroll: true })}>
                                        {t('remove')}
                                    </button>
                                </div>
                                <strong>{line.line_total_formatted}</strong>
                            </div>
                        ))}
                        <div className="total-row" style={{ margin: '22px 0' }}>
                            <span>{t('subtotal')}</span>
                            <strong>{subtotal} {t('price')}</strong>
                        </div>
                        <Link className="primary" href="/checkout">{t('checkout')}</Link>
                    </>
                )}
            </section>
        </StoreLayout>
    );
}
