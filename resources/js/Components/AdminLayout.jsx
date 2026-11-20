import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CloseIcon, GridIcon, LogoutIcon, MailIcon, MenuIcon, ReceiptIcon, StoreIcon, TagIcon, TruckIcon, TypeIcon } from './AdminIcons';
import { useLocale } from '../hooks';

const groups = [
    {
        label: 'navOperate',
        items: [
            ['/admin', 'dashboard', GridIcon],
            ['/admin/products', 'products', TagIcon],
            ['/admin/orders', 'adminOrders', ReceiptIcon],
        ],
    },
    {
        label: 'navStore',
        items: [
            ['/admin/shipping', 'shippingAdmin', TruckIcon],
            ['/admin/messages', 'messages', MailIcon],
            ['/admin/settings', 'settings', TypeIcon],
        ],
    },
];

function isCurrent(url, href) {
    if (href === '/admin') {
        return url === '/admin' || url === '/admin/';
    }

    return url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);
}

export default function AdminLayout({ title, lede, actions, back, children }) {
    const { url, props } = usePage();
    const { auth, adminNav, flash } = props;
    const { t } = useLocale();
    const [open, setOpen] = useState(false);
    const unread = adminNav?.unread ?? 0;

    useEffect(() => {
        setOpen(false);
    }, [url]);

    useEffect(() => {
        function onKey(event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        }

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, []);

    return (
        <div className={open ? 'admin-shell is-nav-open' : 'admin-shell'}>
            <Head title={`${title} · شيزين`} />
            <div className="admin-topbar">
                <button type="button" className="icon-button" aria-expanded={open} aria-label={open ? t('closeMenu') : t('openMenu')} onClick={() => setOpen((value) => !value)}>
                    {open ? <CloseIcon /> : <MenuIcon />}
                </button>
                <Link href="/admin" aria-label={t('dashboard')}>
                    <img className="admin-logo" src="/brand/logo.svg" alt="شيزين SHEZAIN" />
                </Link>
            </div>
            {open && <button type="button" className="admin-scrim" aria-label={t('closeMenu')} onClick={() => setOpen(false)} />}
            <aside className="admin-nav">
                <div className="admin-brand">
                    <Link href="/admin" aria-label={t('dashboard')}>
                        <img className="admin-logo" src="/brand/logo.svg" alt="شيزين SHEZAIN" />
                    </Link>
                </div>
                <nav aria-label={t('admin')}>
                    {groups.map((group) => (
                        <div key={group.label}>
                            <p className="admin-group">{t(group.label)}</p>
                            {group.items.map(([href, key, Icon]) => (
                                <Link key={href} href={href} className={isCurrent(url, href) ? 'active' : undefined} aria-current={isCurrent(url, href) ? 'page' : undefined}>
                                    <Icon />
                                    <span>{t(key)}</span>
                                    {key === 'messages' && unread > 0 && <span className="nav-count">{unread}</span>}
                                </Link>
                            ))}
                        </div>
                    ))}
                </nav>
                <div className="admin-nav-foot">
                    {auth.user?.name && <p className="admin-user">{auth.user.name}</p>}
                    <Link className="admin-store" href="/">
                        <StoreIcon />
                        <span>{t('viewStore')}</span>
                    </Link>
                    <Link className="admin-logout" href="/logout" method="post" as="button">
                        <LogoutIcon />
                        <span>{t('signOut')}</span>
                    </Link>
                </div>
            </aside>
            <div className="admin-main">
                {flash?.success && <div className="flash" role="status">{flash.success}</div>}
                {flash?.error && <div className="flash error" role="alert">{flash.error}</div>}
                <header className="admin-head">
                    <div>
                        {back && <Link className="back-link" href={back.href}>{back.label}</Link>}
                        <h1>{title}</h1>
                        {lede && <p>{lede}</p>}
                    </div>
                    {actions && <div className="admin-actions">{actions}</div>}
                </header>
                {children}
            </div>
        </div>
    );
}
