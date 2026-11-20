import { useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { CloseIcon } from '../../../Components/AdminIcons';
import { IconButton, Sheet } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

const emptyVariant = { size: '', sku: '', stock: 0 };

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
        variants: product?.variants?.map((variant) => ({ id: variant.id, size: variant.size, sku: variant.sku, stock: variant.stock })) ?? [{ ...emptyVariant }],
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

    function setVariant(index, field, value) {
        const variants = form.data.variants.map((variant, variantIndex) => (
            variantIndex === index ? { ...variant, [field]: value } : variant
        ));
        form.setData('variants', variants);
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
                    <div className="variant-head">
                        <span>{t('size')}</span>
                        <span>{t('sku')}</span>
                        <span>{t('stock')}</span>
                        <span className="sr-only">{t('actions')}</span>
                    </div>
                    {form.data.variants.map((variant, index) => (
                        <div className="size-row" key={variant.id ?? index}>
                            <input aria-label={t('size')} placeholder={t('size')} value={variant.size} onChange={(event) => setVariant(index, 'size', event.target.value)} required />
                            <input aria-label={t('sku')} placeholder={t('sku')} value={variant.sku} onChange={(event) => setVariant(index, 'sku', event.target.value)} required />
                            <input aria-label={t('stock')} type="number" min="0" value={variant.stock} onChange={(event) => setVariant(index, 'stock', Number(event.target.value))} required />
                            <IconButton label={t('remove')} onClick={() => form.setData('variants', form.data.variants.filter((_, variantIndex) => variantIndex !== index))}>
                                <CloseIcon />
                            </IconButton>
                        </div>
                    ))}
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
