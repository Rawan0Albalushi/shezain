import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../../Components/AdminLayout';
import { EyeIcon, EyeOffIcon, PencilIcon } from '../../../Components/AdminIcons';
import { applyQuery, IconButton, Ledger, Sheet, StatusMark } from '../../../Components/AdminUi';
import Pager from '../../../Components/Pager';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Index({ products, categories, filters, totals }) {
    const { locale, t } = useLocale();
    const [q, setQ] = useState(filters.q ?? '');

    function apply(patch) {
        applyQuery('/admin/products', { ...filters, q }, patch);
    }

    return (
        <AdminLayout
            title={t('products')}
            lede={t('productsIntro')}
            actions={<Link className="primary" href="/admin/products/create">{t('newProduct')}</Link>}
        >
            <Ledger
                items={[
                    { key: 'all', value: totals.all, label: t('allProducts'), href: '/admin/products', current: !filters.visibility && !filters.stock && !filters.category && !filters.q },
                    { key: 'active', value: totals.active, label: t('visible'), href: '/admin/products?visibility=active', current: filters.visibility === 'active' },
                    { key: 'hidden', value: totals.hidden, label: t('hidden'), href: '/admin/products?visibility=hidden', current: filters.visibility === 'hidden' },
                    { key: 'in', value: totals.in, label: t('inStock'), href: '/admin/products?stock=in', current: filters.stock === 'in' },
                    { key: 'low', value: totals.low, label: t('lowStock'), href: '/admin/products?stock=low', current: filters.stock === 'low' },
                    { key: 'out', value: totals.out, label: t('outOfStock'), href: '/admin/products?stock=out', current: filters.stock === 'out' },
                ]}
            />
            <form
                className="toolbar"
                onSubmit={(event) => {
                    event.preventDefault();
                    apply({ q });
                }}
            >
                <label className="field field-search">
                    <span>{t('find')}</span>
                    <input value={q} onChange={(event) => setQ(event.target.value)} placeholder={t('searchProducts')} />
                </label>
                <label className="field">
                    <span>{t('category')}</span>
                    <select value={filters.category ?? ''} onChange={(event) => apply({ category: event.target.value })}>
                        <option value="">{t('all')}</option>
                        {categories.map((category) => (
                            <option key={category.slug} value={category.slug}>{localized(locale, category, 'name')}</option>
                        ))}
                    </select>
                </label>
                <label className="field">
                    <span>{t('appearance')}</span>
                    <select value={filters.visibility ?? ''} onChange={(event) => apply({ visibility: event.target.value })}>
                        <option value="">{t('every')}</option>
                        <option value="active">{t('visible')}</option>
                        <option value="hidden">{t('hidden')}</option>
                    </select>
                </label>
                <label className="field">
                    <span>{t('stock')}</span>
                    <select value={filters.stock ?? ''} onChange={(event) => apply({ stock: event.target.value })}>
                        <option value="">{t('every')}</option>
                        <option value="in">{t('inStock')}</option>
                        <option value="low">{t('lowStock')}</option>
                        <option value="out">{t('outOfStock')}</option>
                    </select>
                </label>
                <div className="toolbar-actions">
                    <button className="primary" type="submit">{t('apply')}</button>
                    <Link className="quiet" href="/admin/products">{t('clearFilters')}</Link>
                </div>
            </form>
            <Sheet title={t('products')} detail={`${products.total} ${t('results')}`}>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('products')}</th>
                                <th>{t('category')}</th>
                                <th>{t('price')}</th>
                                <th>{t('stock')}</th>
                                <th>{t('appearance')}</th>
                                <th>{t('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.data.length === 0 && (
                                <tr>
                                    <td className="empty-row" colSpan={6}>{t('noMatches')}</td>
                                </tr>
                            )}
                            {products.data.map((product) => (
                                <tr key={product.id}>
                                    <td>
                                        <div className="identity">
                                            {product.image
                                                ? <img src={product.image} alt="" />
                                                : <span className="thumb-fallback" aria-hidden="true" />}
                                            <div>
                                                <Link href={`/admin/products/${product.id}/edit`}>{product.name_ar}</Link>
                                                <div className="sub">{product.name_en}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{locale === 'ar' ? product.category_ar : product.category_en}</td>
                                    <td className="num">{product.price}</td>
                                    <td>
                                        <span className={stockClass(product.stock)}>
                                            {product.stock} {t('pieces')}
                                        </span>
                                    </td>
                                    <td>
                                        <StatusMark status={product.is_active ? 'active' : 'hidden'} label={product.is_active ? t('visible') : t('hidden')} />
                                    </td>
                                    <td className="actions-cell">
                                        <IconButton label={t('edit')} href={`/admin/products/${product.id}/edit`}>
                                            <PencilIcon />
                                        </IconButton>
                                        {product.is_active && (
                                            <IconButton label={t('viewOnStore')} href={`/products/${product.slug}`}>
                                                <EyeIcon />
                                            </IconButton>
                                        )}
                                        <IconButton
                                            label={product.is_active ? t('hideFromStore') : t('showInStore')}
                                            onClick={() => router.patch(`/admin/products/${product.id}/visibility`, {}, { preserveScroll: true })}
                                        >
                                            {product.is_active ? <EyeOffIcon /> : <EyeIcon />}
                                        </IconButton>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Sheet>
            <Pager paginator={products} />
        </AdminLayout>
    );
}

function stockClass(stock) {
    if (stock === 0) {
        return 'status status-out';
    }

    if (stock <= 3) {
        return 'status status-low';
    }

    return 'num';
}
