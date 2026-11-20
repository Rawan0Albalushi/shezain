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
    const colors = [];
    const seenColors = new Set();

    product.variants.forEach((variant) => {
        if (!seenColors.has(variant.color_ar)) {
            seenColors.add(variant.color_ar);
            colors.push(variant);
        }
    });

    const sizes = product.variants.filter((variant) => variant.color_ar === selected?.color_ar);

    function selectVariant(variant) {
        const stock = variant?.stock ?? 0;
        const quantity = stock < 1 ? 1 : Math.min(Math.max(1, form.data.quantity), Math.min(10, stock));
        form.setData({ variant_id: variant.id, quantity });
    }

    function selectColor(colorAr) {
        const match = product.variants.find((variant) => variant.color_ar === colorAr && variant.size === selected?.size && variant.stock > 0)
            ?? product.variants.find((variant) => variant.color_ar === colorAr && variant.stock > 0)
            ?? product.variants.find((variant) => variant.color_ar === colorAr);

        if (match) {
            selectVariant(match);
        }
    }

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
                        {colors.length > 0 && (
                            <fieldset className="option-field">
                                <legend>{t('color')}</legend>
                                <div className="option-row">
                                    {colors.map((color) => {
                                        const active = selected?.color_ar === color.color_ar;

                                        return (
                                            <label key={color.color_ar} className={active ? 'option-chip is-selected' : 'option-chip'}>
                                                <input
                                                    type="radio"
                                                    name="color"
                                                    checked={active}
                                                    onChange={() => selectColor(color.color_ar)}
                                                />
                                                <span className="swatch" style={color.swatch ? { background: color.swatch } : undefined} />
                                                {localized(locale, color, 'color')}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        )}
                        {sizes.length > 0 && (
                            <fieldset className="option-field">
                                <legend>{t('size')}</legend>
                                <div className="option-row">
                                    {sizes.map((variant) => {
                                        const active = selected?.id === variant.id;
                                        const unavailable = variant.stock < 1;

                                        return (
                                            <label key={variant.id} className={`option-chip${active ? ' is-selected' : ''}${unavailable ? ' is-disabled' : ''}`}>
                                                <input
                                                    type="radio"
                                                    name="size"
                                                    checked={active}
                                                    disabled={unavailable}
                                                    onChange={() => selectVariant(variant)}
                                                />
                                                {variant.size}
                                                {unavailable && <span className="sr-only">{t('unavailable')}</span>}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        )}
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
                        {selected && (
                            <p className="muted">{selected.stock < 1 ? t('unavailable') : `${t('stock')} ${selected.stock}`}</p>
                        )}
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
