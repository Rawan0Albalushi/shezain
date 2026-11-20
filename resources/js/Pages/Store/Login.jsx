import { Head, Link, useForm } from '@inertiajs/react';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';

export default function Login() {
    const { t } = useLocale();
    const form = useForm({ email: '', password: '', remember: false });

    return (
        <StoreLayout>
            <Head title={t('login')} />
            <section className="narrow" style={{ maxWidth: 480 }}>
                <h1>{t('login')}</h1>
                <form onSubmit={(event) => { event.preventDefault(); form.post('/login'); }}>
                    <label className="field">
                        <span>{t('email')}</span>
                        <input type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} required />
                        {form.errors.email && <span className="error-text">{form.errors.email}</span>}
                    </label>
                    <label className="field">
                        <span>{t('password')}</span>
                        <input type="password" value={form.data.password} onChange={(event) => form.setData('password', event.target.value)} required />
                    </label>
                    <label className="choice">
                        <input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                        {t('remember')}
                    </label>
                    <button className="primary" disabled={form.processing} type="submit">{t('login')}</button>
                </form>
                <p><Link href="/register">{t('noAccount')} {t('register')}</Link></p>
            </section>
        </StoreLayout>
    );
}
