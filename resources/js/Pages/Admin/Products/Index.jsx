import { Head, Link } from '@inertiajs/react';
import AdminLayout from '../../../Components/AdminLayout';
import Pager from '../../../Components/Pager';
import { useLocale } from '../../../hooks';

export default function Index({ products }) {
    const { t } = useLocale();

    return (
        <AdminLayout title={t('products')}>
            <Head title={t('products')} />
            <p><Link className="primary" href="/admin/products/create">{t('newProduct')}</Link></p>
            <table>
                <tbody>
                    {products.data.map((product) => (
                        <tr key={product.id}>
                            <td><Link href={`/admin/products/${product.id}/edit`}>{product.name_ar}</Link><div className="muted">{product.name_en}</div></td>
                            <td>{product.category_ar}</td>
                            <td>{product.price}</td>
                            <td>{product.is_active ? t('active') : '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <Pager paginator={products} />
        </AdminLayout>
    );
}
