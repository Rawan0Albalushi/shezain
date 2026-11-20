import { Head, Link, useForm } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';

export default function Register() {
    const { t } = useLocale();
    const form = useForm({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

    return (
        <StoreLayout>
            <Head title={t('register')} />
            <section className="narrow" style={{ maxWidth: 480 }}>
                <h1>{t('register')}</h1>
                <form onSubmit={(event) => { event.preventDefault(); form.post('/register'); }}>
                    {['name', 'phone', 'email'].map((field) => (
                        <label className="field" key={field}>
                            <span>{t(field)}</span>
                            <input type={field === 'email' ? 'email' : 'text'} value={form.data[field]} onChange={(event) => form.setData(field, event.target.value)} required />
                            {form.errors[field] && <span className="error-text">{form.errors[field]}</span>}
                        </label>
                    ))}
                    <label className="field">
                        <span>{t('password')}</span>
                        <input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} required />
                        {form.errors.password && <span className="error-text">{form.errors.password}</span>}
                    </label>
                    <label className="field">
                        <span>{t('confirmPassword')}</span>
                        <input type="password" value={form.data.password_confirmation} onChange={(event) => form.setData('password_confirmation', event.target.value)} required />
                    </label>
                    <button className="primary" disabled={form.processing} type="submit">{t('register')}</button>
                </form>
                <p><Link href="/login">{t('hasAccount')} {t('login')}</Link></p>
            </section>
        </StoreLayout>
    );
}
