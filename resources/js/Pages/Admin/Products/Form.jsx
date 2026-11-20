import { useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { CloseIcon } from '../../../Components/AdminIcons';
import { IconButton, Sheet } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

const emptyVariant = { size: '', color_ar: '', color_en: '', color_hex: '', sku: '', stock: 0 };

const colorSuggestions = [
    { ar: 'أسود', en: 'Black', hex: '#1C1C1C' },
    { ar: 'كحلي', en: 'Navy', hex: '#1B2A4A' },
    { ar: 'بيج', en: 'Beige', hex: '#D7C4A3' },
    { ar: 'رملي', en: 'Sand', hex: '#C4A574' },
    { ar: 'بني', en: 'Brown', hex: '#6B4423' },
    { ar: 'عنابي', en: 'Burgundy', hex: '#6B2430' },
    { ar: 'رمادي', en: 'Grey', hex: '#8A8680' },
    { ar: 'زيتي', en: 'Olive', hex: '#4E5340' },
    { ar: 'حبري', en: 'Ink', hex: '#16181D' },
    { ar: 'سكري', en: 'Cream', hex: '#F3E6D0' },
    { ar: 'أبيض', en: 'White', hex: '#F7F4EF' },
    { ar: 'أخضر', en: 'Green', hex: '#2F4A3C' },
];

export default function Form({ categories, product }) {
    const { locale, t } = useLocale();
    const form = useForm({
        category_id: product?.category_id ?? categories[0]?.id ?? '',
        name_ar: product?.name_ar ?? '',
        name_en: product?.name_en ?? '',
        description_ar: product?.description_ar ?? '',
        description_en: product?.description_en ?? '',
        fabric_ar: product?.fabric_ar ?? '',
        fabric_en: product?.fabric_en ?? '',
        price: product?.price_omr ?? '',
        is_active: product?.is_active ?? true,
        variants: product?.variants?.map((variant) => ({
            id: variant.id,
            size: variant.size,
            color_ar: variant.color_ar ?? '',
            color_en: variant.color_en ?? '',
            color_hex: variant.color_hex ?? '',
            sku: variant.sku,
            stock: variant.stock,
        })) ?? [{ ...emptyVariant }],
        images: [],
        remove_images: [],
    });

    function submit(event) {
        event.preventDefault();
        const options = { forceFormData: true };

        if (product) {
            form.put(`/admin/products/${product.id}`, options);
        } else {
            form.post('/admin/products', options);
        }
    }

    function setVariant(index, changes) {
        form.setData('variants', form.data.variants.map((variant, variantIndex) => (
            variantIndex === index ? { ...variant, ...changes } : variant
        )));
    }

    function setColor(index, field, value) {
        const current = form.data.variants[index];
        const known = colorSuggestions.find((color) => (
            field === 'color_ar'
                ? color.ar === value.trim()
                : color.en.toLowerCase() === value.trim().toLowerCase()
        ));
        const changes = { [field]: value };

        if (known && field === 'color_ar' && current.color_en.trim() === '') {
            changes.color_en = known.en;
        }

        if (known && field === 'color_en' && current.color_ar.trim() === '') {
            changes.color_ar = known.ar;
        }

        if (known && current.color_hex.trim() === '') {
            changes.color_hex = known.hex;
        }

        setVariant(index, changes);
    }

    return (
        <AdminLayout
            title={product ? product.name_ar : t('newProduct')}
            lede={t('productFormIntro')}
            back={{ href: '/admin/products', label: t('backToProducts') }}
        >
            <form className="admin-form" onSubmit={submit}>
                <Sheet title={t('productDetails')}>
                    <div className="form-grid sheet-fields">
                        <label className="field">
                            <span>{t('category')}</span>
                            <select value={form.data.category_id} onChange={(event) => form.setData('category_id', Number(event.target.value))}>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>{localized(locale, category, 'name')}</option>
                                ))}
                            </select>
                        </label>
                        <label className="field">
                            <span>{t('price')}</span>
                            <input value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} required />
                            {form.errors.price && <span className="error-text">{form.errors.price}</span>}
                        </label>
                        {[
                            ['name_ar', 'nameAr'],
                            ['name_en', 'nameEn'],
                            ['fabric_ar', 'fabricAr'],
                            ['fabric_en', 'fabricEn'],
                        ].map(([field, label]) => (
                            <label className="field" key={field}>
                                <span>{t(label)}</span>
                                <input value={form.data[field] ?? ''} onChange={(event) => form.setData(field, event.target.value)} required={field.startsWith('name')} />
                                {form.errors[field] && <span className="error-text">{form.errors[field]}</span>}
                            </label>
                        ))}
                        <label className="field">
                            <span>{t('descriptionAr')}</span>
                            <textarea value={form.data.description_ar} onChange={(event) => form.setData('description_ar', event.target.value)} required />
                            {form.errors.description_ar && <span className="error-text">{form.errors.description_ar}</span>}
                        </label>
                        <label className="field">
                            <span>{t('descriptionEn')}</span>
                            <textarea value={form.data.description_en} onChange={(event) => form.setData('description_en', event.target.value)} required />
                            {form.errors.description_en && <span className="error-text">{form.errors.description_en}</span>}
                        </label>
                    </div>
                    <label className="choice">
                        <input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                        {t('active')}
                    </label>
                </Sheet>
                <Sheet title={t('variants')} detail={t('variantsIntro')}>
                    {form.errors.variants && <p className="error-text">{form.errors.variants}</p>}
                    <datalist id="abaya-colors">
                        {colorSuggestions.map((color) => <option key={color.ar} value={color.ar}>{color.en}</option>)}
                    </datalist>
                    <div className="variant-list">
                        {form.data.variants.map((variant, index) => {
                            const hex = /^#[0-9A-Fa-f]{6}$/.test(variant.color_hex) ? variant.color_hex : null;

                            return (
                                <div className="variant-card" key={variant.id ?? index}>
                                    <div className="variant-fields">
                                        <label className="field">
                                            <span>{t('size')}</span>
                                            <input value={variant.size} onChange={(event) => setVariant(index, { size: event.target.value })} required />
                                            {form.errors[`variants.${index}.size`] && <span className="error-text">{form.errors[`variants.${index}.size`]}</span>}
                                        </label>
                                        <label className="field">
                                            <span>{t('colorAr')}</span>
                                            <input list="abaya-colors" value={variant.color_ar} onChange={(event) => setColor(index, 'color_ar', event.target.value)} required />
                                            {form.errors[`variants.${index}.color_ar`] && <span className="error-text">{form.errors[`variants.${index}.color_ar`]}</span>}
                                        </label>
                                        <label className="field">
                                            <span>{t('colorEn')}</span>
                                            <input value={variant.color_en} onChange={(event) => setColor(index, 'color_en', event.target.value)} required />
                                            {form.errors[`variants.${index}.color_en`] && <span className="error-text">{form.errors[`variants.${index}.color_en`]}</span>}
                                        </label>
                                        <label className="field">
                                            <span>{t('colorHex')} <span className="swatch" style={hex ? { background: hex } : undefined} /></span>
                                            <input value={variant.color_hex} placeholder="#1C1C1C" onChange={(event) => setVariant(index, { color_hex: event.target.value })} />
                                            {form.errors[`variants.${index}.color_hex`] && <span className="error-text">{form.errors[`variants.${index}.color_hex`]}</span>}
                                        </label>
                                        <label className="field">
                                            <span>{t('sku')}</span>
                                            <input value={variant.sku} onChange={(event) => setVariant(index, { sku: event.target.value })} required />
                                            {form.errors[`variants.${index}.sku`] && <span className="error-text">{form.errors[`variants.${index}.sku`]}</span>}
                                        </label>
                                        <label className="field">
                                            <span>{t('stock')}</span>
                                            <input type="number" min="0" value={variant.stock} onChange={(event) => setVariant(index, { stock: Number(event.target.value) })} required />
                                            {form.errors[`variants.${index}.stock`] && <span className="error-text">{form.errors[`variants.${index}.stock`]}</span>}
                                        </label>
                                    </div>
                                    <IconButton label={t('remove')} onClick={() => form.setData('variants', form.data.variants.filter((_, variantIndex) => variantIndex !== index))}>
                                        <CloseIcon />
                                    </IconButton>
                                </div>
                            );
                        })}
                    </div>
                    <button type="button" className="secondary" onClick={() => form.setData('variants', [...form.data.variants, { ...emptyVariant }])}>{t('addSize')}</button>
                </Sheet>
                <Sheet title={t('images')}>
                    <div className="thumbs admin-thumbs">
                        {(product?.image_records ?? []).map((image) => {
                            const marked = form.data.remove_images.includes(image.id);

                            return (
                                <label key={image.id} className={marked ? 'is-marked' : undefined}>
                                    <img src={image.url} alt="" />
                                    <span>{marked ? t('willRemove') : t('removeImage')}</span>
                                    <input
                                        type="checkbox"
                                        checked={marked}
                                        onChange={(event) => {
                                            const ids = event.target.checked
                                                ? [...form.data.remove_images, image.id]
                                                : form.data.remove_images.filter((id) => id !== image.id);
                                            form.setData('remove_images', ids);
                                        }}
                                    />
                                </label>
                            );
                        })}
                    </div>
                    <label className="field">
                        <span>{t('addImages')}</span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" multiple onChange={(event) => form.setData('images', Array.from(event.target.files))} />
                    </label>
                </Sheet>
                <button className="primary" disabled={form.processing} type="submit">{t('save')}</button>
            </form>
        </AdminLayout>
    );
}
