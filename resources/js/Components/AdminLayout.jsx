import { Link, usePage } from '@inertiajs/react';
import { useLocale } from '../hooks';

const links = [
    ['/admin', 'dashboard'],
    ['/admin/products', 'products'],
    ['/admin/orders', 'orders'],
    ['/admin/shipping', 'shippingAdmin'],
    ['/admin/messages', 'messages'],
    ['/admin/settings', 'settings'],
];

export default function AdminLayout({ title, children }) {
    const { url } = usePage();
    const { t } = useLocale();

    return (
        <div className="admin-shell">
            <aside className="admin-nav">
                <Link href="/"><img className="logo" src="/brand/logo.svg" alt="SHEZAIN" /></Link>
                {links.map(([href, key]) => (
                    <Link key={href} href={href} className={url === href || (href !== '/admin' && url.startsWith(href)) ? 'active' : ''}>
                        {t(key)}
                    </Link>
                ))}
                <Link href="/logout" method="post">{t('logout')}</Link>
            </aside>
            <div className="admin-main">
                <h1 style={{ fontSize: 32 }}>{title}</h1>
                {children}
            </div>
        </div>
    );
}
