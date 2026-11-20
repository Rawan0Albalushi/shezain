import { Head } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

export default function Page({ page, rates }) {
    const { locale, t } = useLocale();
    const title = localized(locale, page, 'title');

    return (
        <StoreLayout>
            <Head title={title} />
            <section className="narrow">
                <h1>{title}</h1>
                <p className="prose">{localized(locale, page, 'body')}</p>
                {rates.length > 0 && (
                    <div className="rate-list">
                        {rates.map((rate) => (
                            <div key={rate.governorate_en}>
                                <span>{localized(locale, rate, 'governorate')}</span>
                                <span>{rate.fee} {t('price')}</span>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </StoreLayout>
    );
}
