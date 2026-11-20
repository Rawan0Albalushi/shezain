import { useForm } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { Sheet } from '../../Components/AdminUi';
import { useLocale } from '../../hooks';

const groups = [
    { title: 'setTopline', keys: ['topline_ar', 'topline_en'] },
    { title: 'setHero', keys: ['hero_title_ar', 'hero_title_en', 'hero_intro_ar', 'hero_intro_en', 'hero_button_ar', 'hero_button_en', 'hero_foot_ar', 'hero_foot_en'] },
    { title: 'setCollection', keys: ['collection_kicker_ar', 'collection_kicker_en'] },
    { title: 'setBrand', keys: ['brand_title_ar', 'brand_title_en', 'brand_text_ar', 'brand_text_en'] },
    { title: 'setContact', keys: ['phone', 'email'] },
];

function fieldLabel(t, key) {
    if (key === 'phone' || key === 'email') {
        return t(key);
    }

    return t(`setting_${key}`);
}

function isLong(key) {
    return key.includes('intro') || key.includes('title') || key.includes('text') || key.includes('foot');
}

export default function Settings({ settings }) {
    const { t } = useLocale();
    const form = useForm({ ...settings });

    return (
        <AdminLayout title={t('settings')} lede={t('settingsIntro')}>
            <form className="admin-form" onSubmit={(event) => { event.preventDefault(); form.put('/admin/settings'); }}>
                {groups.map((group) => {
                    const keys = group.keys.filter((key) => key in settings);

                    if (keys.length === 0) {
                        return null;
                    }

                    return (
                        <Sheet key={group.title} title={t(group.title)}>
                            <div className="form-grid sheet-fields">
                                {keys.map((key) => (
                                    <label className="field" key={key}>
                                        <span>{fieldLabel(t, key)}</span>
                                        {isLong(key)
                                            ? <textarea value={form.data[key] ?? ''} onChange={(event) => form.setData(key, event.target.value)} />
                                            : <input value={form.data[key] ?? ''} onChange={(event) => form.setData(key, event.target.value)} />}
                                        {form.errors[key] && <span className="error-text">{form.errors[key]}</span>}
                                    </label>
                                ))}
                            </div>
                        </Sheet>
                    );
                })}
                <button className="primary" type="submit" disabled={form.processing}>{t('save')}</button>
            </form>
        </AdminLayout>
    );
}
