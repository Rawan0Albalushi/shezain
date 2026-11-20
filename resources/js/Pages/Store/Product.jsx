import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

export default function Product({ product }) {
    const { locale, t } = useLocale();
    const [photo, setPhoto] = useState(0);
    const available = product.variants.find((variant) => variant.stock > 0) ?? product.variants[0];
    const form = useForm({
        variant_id: available?.id ?? '',
        quantity: 1,
    });
    const selected = product.variants.find((variant) => variant.id === Number(form.data.variant_id));

    function submit(event) {
        event.preventDefault();
        form.post('/cart');
    }

    return (
        <StoreLayout>
            <Head title={localized(locale, product, 'name')} />
            <article className="page">
                <div className="detail-grid">
                    <div>
                        <div className="detail-photo">
                            {product.images[photo] && <img src={product.images[photo]} alt={localized(locale, product, 'name')} />}
                        </div>
                        {product.images.length > 1 && (
                            <div className="thumbs">
                                {product.images.map((image, index) => (
                                    <button key={image} type="button" className={index === photo ? 'selected' : ''} onClick={() => setPhoto(index)}>
                                        <img src={image} alt="" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                    <form onSubmit={submit}>
                        <span className="eyebrow">{localized(locale, product.category, 'name')}</span>
                        <h1 style={{ fontSize: 40 }}>{localized(locale, product, 'name')}</h1>
                        <p className="price" style={{ fontSize: 22 }}>{product.price_formatted} {t('price')}</p>
                        <p className="prose">{localized(locale, product, 'description')}</p>
                        <p className="muted">{t('fabric')}: {localized(locale, product, 'fabric')}</p>
                        <label>
                            {t('size')}
                            <select value={form.data.variant_id} onChange={(event) => form.setData('variant_id', Number(event.target.value))}>
                                {product.variants.map((variant) => (
                                    <option key={variant.id} value={variant.id} disabled={variant.stock < 1}>
                                        {variant.size} {variant.stock < 1 ? `— ${t('unavailable')}` : `— ${t('stock')} ${variant.stock}`}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <label>
                            {t('quantity')}
                            <input
                                type="number"
                                min="1"
                                max={Math.min(10, selected?.stock || 1)}
                                value={form.data.quantity}
                                onChange={(event) => form.setData('quantity', Number(event.target.value))}
                            />
                        </label>
                        {form.errors.variant_id && <p className="error-text">{form.errors.variant_id}</p>}
                        <button className="primary" type="submit" disabled={form.processing || !selected || selected.stock < 1}>
                            {t('add')}
                        </button>
                    </form>
                </div>
            </article>
        </StoreLayout>
    );
}
