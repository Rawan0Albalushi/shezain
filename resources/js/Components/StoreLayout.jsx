import { Link, usePage } from '@inertiajs/react';
import { localized } from '../i18n';
import { useLocale } from '../hooks';

export default function StoreLayout({ children }) {
    const { auth, categories, cartCount, flash, filters, settings } = usePage().props;
    const { locale, t, switchLocale } = useLocale();
    const category = filters?.category ?? 'all';

    return (
        <>
            <div className="topline">{settings?.[`topline_${locale}`]}</div>
            <header className="site-header">
                <Link href="/" aria-label={t('home')}>
                    <img className="logo" src="/brand/logo.svg" alt="شيزين SHEZAIN" />
                </Link>
                <nav className="site-nav" aria-label={t('navLabel')}>
                    <Link href="/?category=all" className={category === 'all' ? 'active' : ''}>{t('all')}</Link>
                    {categories.map((item) => (
                        <Link
                            key={item.slug}
                            href={`/?category=${item.slug}`}
                            className={category === item.slug ? 'active' : ''}
                        >
                            {locale === 'ar' ? item.name_ar.replace('عبايات ', '') : item.name_en.replace(' abayas', '')}
                        </Link>
                    ))}
                </nav>
                <div className="header-tools">
                    <Link className="bag" href="/cart">
                        {t('bag')}
                        <span>{cartCount}</span>
                    </Link>
                    <button type="button" className="language" lang={locale === 'ar' ? 'en' : 'ar'} aria-label={t('languageLabel')} onClick={switchLocale}>
                        {t('language')}
                    </button>
                </div>
            </header>
            {flash?.success && <div className="flash" role="status">{flash.success}</div>}
            {flash?.error && <div className="flash error" role="alert">{flash.error}</div>}
            <main>{children}</main>
            <footer className="site-footer">
                <img className="logo" src="/brand/logo.svg" alt="SHEZAIN" />
                <div className="footer-links">
                    <Link href="/pages/about">{t('about')}</Link>
                    <Link href="/pages/shipping">{t('shipping')}</Link>
                    <Link href="/pages/sizes">{t('sizes')}</Link>
                    <Link href="/pages/privacy">{t('privacy')}</Link>
                    <Link href="/contact">{t('contact')}</Link>
                    {auth.user ? <Link href="/account/orders">{t('orders')}</Link> : <Link href="/login">{t('login')}</Link>}
                    {auth.user?.is_admin && <Link href="/admin">{t('admin')}</Link>}
                </div>
                <p>{t('footer')}</p>
                <span>© {new Date().getFullYear()} {locale === 'ar' ? 'شيزين' : 'SHEZAIN'}</span>
            </footer>
        </>
    );
}

export function categoryName(locale, category) {
    return localized(locale, category, 'name');
}
