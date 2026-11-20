import { Head, useForm } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';

export default function Contact({ phone, email }) {
    const { t } = useLocale();
    const form = useForm({ name: '', email: '', phone: '', body: '' });

    return (
        <StoreLayout>
            <Head title={t('contact')} />
            <section className="narrow" style={{ maxWidth: 640 }}>
                <h1>{t('contact')}</h1>
                <p className="muted">{phone} · {email}</p>
                <form onSubmit={(event) => { event.preventDefault(); form.post('/contact'); }}>
                    {['name', 'email', 'phone'].map((field) => (
                        <label className="field" key={field}>
                            <span>{t(field)}</span>
                            <input value={form.data[field]} onChange={(event) => form.setData(field, event.target.value)} required={field !== 'phone'} />
                            {form.errors[field] && <span className="error-text">{form.errors[field]}</span>}
                        </label>
                    ))}
                    <label className="field">
                        <span>{t('message')}</span>
                        <textarea value={form.data.body} onChange={(event) => form.setData('body', event.target.value)} required />
                        {form.errors.body && <span className="error-text">{form.errors.body}</span>}
                    </label>
                    <button className="primary" disabled={form.processing} type="submit">{t('send')}</button>
                </form>
            </section>
        </StoreLayout>
    );
}
