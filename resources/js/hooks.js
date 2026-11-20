import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { translate } from './i18n';

export function useLocale() {
    const { locale } = usePage().props;

    useEffect(() => {
        document.documentElement.lang = locale;
        document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
    }, [locale]);

    return {
        locale,
        t: (key) => translate(locale, key),
        switchLocale: () => router.post('/locale', { locale: locale === 'ar' ? 'en' : 'ar' }, { preserveScroll: true }),
    };
}
