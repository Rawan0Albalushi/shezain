import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import { useLocale } from '../../../hooks';

const emptyVariant = { size: '', sku: '', stock: 0 };

export default function Form({ categories, product }) {
    const { t } = useLocale();
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
        <AdminLayout title={product ? product.name_ar : t('newProduct')}>
            <Head title={product ? product.name_ar : t('newProduct')} />
            <form onSubmit={submit}>
                <div className="form-grid">
                    <label className="field">
                        <span>{t('category')}</span>
                        <select value={form.data.category_id} onChange={(event) => form.setData('category_id', Number(event.target.value))}>
                            {categories.map((category) => <option key={category.id} value={category.id}>{category.name_ar}</option>)}
                        </select>
                    </label>
                    <label className="field">
                        <span>{t('price')}</span>
                        <input value={form.data.price} onChange={(event) => form.setData('price', event.target.value)} required />
                        {form.errors.price && <span className="error-text">{form.errors.price}</span>}
                    </label>
                    {['name_ar', 'name_en', 'fabric_ar', 'fabric_en'].map((field) => (
                        <label className="field" key={field}>
                            <span>{field}</span>
                            <input value={form.data[field] ?? ''} onChange={(event) => form.setData(field, event.target.value)} required={field.startsWith('name')} />
                        </label>
                    ))}
                    <label className="field">
                        <span>{t('description')} AR</span>
                        <textarea value={form.data.description_ar} onChange={(event) => form.setData('description_ar', event.target.value)} required />
                    </label>
                    <label className="field">
                        <span>{t('description')} EN</span>
                        <textarea value={form.data.description_en} onChange={(event) => form.setData('description_en', event.target.value)} required />
                    </label>
                </div>
                <label className="choice">
                    <input type="checkbox" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                    {t('active')}
                </label>
                <h2>{t('variants')}</h2>
                {form.errors.variants && <p className="error-text">{form.errors.variants}</p>}
                {form.data.variants.map((variant, index) => (
                    <div className="size-row" key={variant.id ?? index}>
                        <input placeholder={t('size')} value={variant.size} onChange={(event) => setVariant(index, 'size', event.target.value)} required />
                        <input placeholder={t('sku')} value={variant.sku} onChange={(event) => setVariant(index, 'sku', event.target.value)} required />
                        <input type="number" min="0" value={variant.stock} onChange={(event) => setVariant(index, 'stock', Number(event.target.value))} required />
                        <button type="button" className="text-button" onClick={() => form.setData('variants', form.data.variants.filter((_, variantIndex) => variantIndex !== index))}>{t('remove')}</button>
                    </div>
                ))}
                <button type="button" className="text-button" onClick={() => form.setData('variants', [...form.data.variants, { ...emptyVariant }])}>{t('addSize')}</button>
                <h2>{t('images')}</h2>
                <div className="thumbs">
                    {(product?.image_records ?? []).map((image) => (
                        <label key={image.id}>
                            <img src={image.url} alt="" />
                            <input
                                type="checkbox"
                                onChange={(event) => {
                                    const ids = event.target.checked
                                        ? [...form.data.remove_images, image.id]
                                        : form.data.remove_images.filter((id) => id !== image.id);
                                    form.setData('remove_images', ids);
                                }}
                            />
                        </label>
                    ))}
                </div>
                <input type="file" accept="image/jpeg,image/png,image/webp" multiple onChange={(event) => form.setData('images', Array.from(event.target.files))} />
                <p><button className="primary" disabled={form.processing} type="submit">{t('save')}</button></p>
            </form>
        </AdminLayout>
    );
}
