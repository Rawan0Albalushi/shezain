import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import { useLocale } from '../../hooks';

export default function Settings({ settings }) {
    const { t } = useLocale();
    const form = useForm({ ...settings });

    return (
        <AdminLayout title={t('settings')}>
            <Head title={t('settings')} />
            <form onSubmit={(event) => { event.preventDefault(); form.put('/admin/settings'); }}>
                {Object.keys(settings).map((key) => (
                    <label className="field" key={key}>
                        <span>{key}</span>
                        {key.includes('intro') || key.includes('title') || key.includes('text') || key.includes('foot')
                            ? <textarea value={form.data[key] ?? ''} onChange={(event) => form.setData(key, event.target.value)} />
                            : <input value={form.data[key] ?? ''} onChange={(event) => form.setData(key, event.target.value)} />}
                    </label>
                ))}
                <button className="primary" type="submit">{t('save')}</button>
            </form>
        </AdminLayout>
    );
}
