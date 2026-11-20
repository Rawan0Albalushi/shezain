import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';
import { localized } from '../../../i18n';

export default function Show({ rate }) {
    const { locale, t } = useLocale();

    return (
        <AdminLayout
            title={localized(locale, rate, 'governorate')}
            lede={locale === 'ar' ? rate.governorate_en : rate.governorate_ar}
            back={{ href: '/admin/shipping', label: t('backToShipping') }}
            actions={<StatusMark status={rate.is_active ? 'active' : 'hidden'} label={rate.is_active ? t('activeRates') : t('pausedRates')} />}
        >
            <Sheet title={t('shippingAdmin')}>
                <dl className="meta">
                    <dt>{t('nameAr')}</dt>
                    <dd>{rate.governorate_ar}</dd>
                    <dt>{t('nameEn')}</dt>
                    <dd>{rate.governorate_en}</dd>
                    <dt>{t('shippingFee')}</dt>
                    <dd className="num">{rate.fee} {t('price')}</dd>
                    <dt>{t('appearance')}</dt>
                    <dd>{rate.is_active ? t('activeRates') : t('pausedRates')}</dd>
                </dl>
            </Sheet>
        </AdminLayout>
    );
}
