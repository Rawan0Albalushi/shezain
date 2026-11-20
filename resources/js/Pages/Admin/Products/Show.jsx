import { Link } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Show({ product }) {
    const { locale, t } = useLocale();
    const [photo, setPhoto] = useState(0);
    const images = product.images ?? [];
    const current = images[photo] ?? images[0];

    return (
        <AdminLayout
            title={localized(locale, product, 'name')}
            lede={locale === 'ar' ? product.name_en : product.name_ar}
            back={{ href: '/admin/products', label: t('backToProducts') }}
            actions={(
                <>
                    <StatusMark status={product.is_active ? 'active' : 'hidden'} label={product.is_active ? t('visible') : t('hidden')} />
                    <Link className="quiet" href={`/admin/products/${product.id}/edit`}>{t('edit')}</Link>
                    {product.is_active && <Link className="quiet" href={`/products/${product.slug}`}>{t('viewOnStore')}</Link>}
                </>
            )}
        >
            <Sheet title={t('productDetails')}>
                <div className="admin-product-view">
                    <div className="admin-gallery">
                        {current ? (
                            <div className="admin-photo">
                                <img src={current} alt="" />
                            </div>
                        ) : (
                            <p className="sheet-note">{t('noPhotos')}</p>
                        )}
                        {images.length > 1 && (
                            <div className="admin-thumbs">
                                {images.map((image, index) => (
                                    <button
                                        type="button"
                                        key={`${image}-${index}`}
                                        className={index === photo ? 'is-current' : undefined}
                                        aria-label={t('slide')}
                                        aria-current={index === photo ? 'true' : undefined}
                                        onClick={() => setPhoto(index)}
                                    >
                                        <img src={image} alt="" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>
                    <dl className="meta">
                        <dt>{t('nameAr')}</dt>
                        <dd>{product.name_ar}</dd>
                        <dt>{t('nameEn')}</dt>
                        <dd>{product.name_en}</dd>
                        <dt>{t('category')}</dt>
                        <dd>{locale === 'ar' ? product.category_ar : product.category_en}</dd>
                        <dt>{t('price')}</dt>
                        <dd className="num">{product.price}</dd>
                        <dt>{t('stock')}</dt>
                        <dd>{product.stock} {t('pieces')}</dd>
                        {product.fabric_ar && (
                            <>
                                <dt>{t('fabricAr')}</dt>
                                <dd>{product.fabric_ar}</dd>
                            </>
                        )}
                        {product.fabric_en && (
                            <>
                                <dt>{t('fabricEn')}</dt>
                                <dd>{product.fabric_en}</dd>
                            </>
                        )}
                    </dl>
                </div>
            </Sheet>
            {(product.description_ar || product.description_en) && (
                <div className="admin-split">
                    {product.description_ar && (
                        <Sheet title={t('descriptionAr')}>
                            <p className="admin-prose">{product.description_ar}</p>
                        </Sheet>
                    )}
                    {product.description_en && (
                        <Sheet title={t('descriptionEn')}>
                            <p className="admin-prose" dir="ltr">{product.description_en}</p>
                        </Sheet>
                    )}
                </div>
            )}
            <Sheet title={t('variants')}>
                {product.variants.length === 0 ? (
                    <p className="sheet-note">{t('noVariants')}</p>
                ) : (
                    <div className="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>{t('color')}</th>
                                    <th>{t('size')}</th>
                                    <th>{t('sku')}</th>
                                    <th>{t('stock')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {product.variants.map((variant) => (
                                    <tr key={variant.sku}>
                                        <td>
                                            <span className="color-cell">
                                                <span className="swatch" style={variant.swatch ? { background: variant.swatch } : undefined} />
                                                {localized(locale, variant, 'color')}
                                            </span>
                                            <div className="sub">{locale === 'ar' ? variant.color_en : variant.color_ar}</div>
                                        </td>
                                        <td>{variant.size}</td>
                                        <td className="num">{variant.sku}</td>
                                        <td className="num">{variant.stock}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Sheet>
        </AdminLayout>
    );
}
