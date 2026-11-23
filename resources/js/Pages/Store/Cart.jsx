import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized, pieceCount, stockLeft, variantSummary } from '../../i18n';

export default function Cart({ lines, subtotal, suggestions = [] }) {
    const { locale, t } = useLocale();
    const [pendingId, setPendingId] = useState(null);
    const pieces = lines.reduce((sum, line) => sum + line.quantity, 0);

    function setQuantity(line, quantity) {
        if (pendingId !== null || quantity === line.quantity) {
            return;
        }

        const max = Math.min(10, line.stock);
        const next = Math.min(Math.max(1, quantity), max);

        if (next === line.quantity) {
            return;
        }

        setPendingId(line.variant_id);
        router.patch(`/cart/${line.variant_id}`, { quantity: next }, {
            preserveScroll: true,
            onFinish: () => setPendingId(null),
        });
    }

    function remove(line) {
        if (pendingId !== null) {
            return;
        }

        setPendingId(line.variant_id);
        router.delete(`/cart/${line.variant_id}`, {
            preserveScroll: true,
            onFinish: () => setPendingId(null),
        });
    }

    return (
        <StoreLayout>
            <Head title={t('bag')} />
            <section className={lines.length === 0 ? 'page bag-page is-empty' : 'page bag-page'}>
                <header className="bag-head">
                    <h1>{t('bag')}</h1>
                    {lines.length > 0 && <p className="bag-count">{pieceCount(locale, pieces)}</p>}
                </header>
                {lines.length === 0 ? (
                    <>
                        <div className="bag-empty">
                            <p>{t('emptyBag')}</p>
                            <p className="bag-note">{t('emptyBagHint')}</p>
                            <Link className="primary" href="/">{t('continue')}</Link>
                        </div>
                        {suggestions.length > 0 && (
                            <div className="bag-picks">
                                <h2>{t('fromCollection')}</h2>
                                <div className="products">
                                    {suggestions.map((product) => (
                                        <Link className="product" key={product.slug} href={`/products/${product.slug}`}>
                                            <div className="product-image">
                                                {product.image && <img src={product.image} alt="" />}
                                                <span className="tag">{localized(locale, product.category, 'name')}</span>
                                            </div>
                                            <h3>{localized(locale, product, 'name')}</h3>
                                            <div className="product-meta">
                                                <span className="card-options">
                                                    <span className="sr-only">{(product.colors ?? []).map((color) => localized(locale, color, 'color')).join(' · ')}</span>
                                                    {(product.colors ?? []).map((color) => (
                                                        <span key={color.color_ar} className="swatch" style={color.swatch ? { background: color.swatch } : undefined} />
                                                    ))}
                                                    <span>{product.sizes.join(' · ')}</span>
                                                </span>
                                                <span className="price">{product.price_formatted} {t('price')}</span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="bag-layout">
                        <div className="bag-lines">
                            {lines.map((line) => {
                                const name = localized(locale, line, 'name');
                                const max = Math.min(10, line.stock);
                                const pending = pendingId === line.variant_id;

                                return (
                                    <article className={pending ? 'cart-row is-pending' : 'cart-row'} key={line.variant_id} aria-busy={pending || undefined}>
                                        <Link className="cart-photo" href={`/products/${line.product_slug}`} aria-label={name}>
                                            {line.image && <img src={line.image} alt="" />}
                                        </Link>
                                        <div className="cart-body">
                                            <div className="cart-title">
                                                <h2 className="cart-name">
                                                    <Link href={`/products/${line.product_slug}`}>{name}</Link>
                                                </h2>
                                                <strong className="cart-total">{line.line_total_formatted} {t('price')}</strong>
                                            </div>
                                            <p className="cart-meta">
                                                {line.swatch && <span className="swatch" style={{ background: line.swatch }} aria-hidden="true" />}
                                                <span>{variantSummary(locale, line)}</span>
                                            </p>
                                            {line.quantity > 1 && (
                                                <p className="cart-unit">{line.unit_price_formatted} {t('price')}</p>
                                            )}
                                            {line.stock <= 3 && <p className="few-left">{stockLeft(locale, line.stock)}</p>}
                                            <div className="cart-actions">
                                                <div className="qty" role="group" aria-label={t('quantity')}>
                                                    <button
                                                        type="button"
                                                        aria-label={t('decrease')}
                                                        disabled={pending || line.quantity <= 1}
                                                        onClick={() => setQuantity(line, line.quantity - 1)}
                                                    >
                                                        −
                                                    </button>
                                                    <span aria-live="polite">{line.quantity}</span>
                                                    <button
                                                        type="button"
                                                        aria-label={t('increase')}
                                                        disabled={pending || line.quantity >= max}
                                                        onClick={() => setQuantity(line, line.quantity + 1)}
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                                <button
                                                    type="button"
                                                    className="text-button"
                                                    aria-label={`${t('remove')} ${name}`}
                                                    disabled={pending}
                                                    onClick={() => remove(line)}
                                                >
                                                    {t('remove')}
                                                </button>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                        <aside className="summary bag-summary">
                            <div className="total-row">
                                <span>{t('subtotal')}</span>
                                <strong>{subtotal} {t('price')}</strong>
                            </div>
                            <p className="bag-note">{t('shippingLater')}</p>
                            {pendingId !== null ? (
                                <span className="primary is-waiting" aria-disabled="true">{t('checkout')}</span>
                            ) : (
                                <Link className="primary" href="/checkout">{t('checkout')}</Link>
                            )}
                            <Link className="bag-continue" href="/">{t('continue')}</Link>
                        </aside>
                    </div>
                )}
            </section>
        </StoreLayout>
    );
}
