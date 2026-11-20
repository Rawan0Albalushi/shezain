import { Head, router } from '@inertiajs/react';
import AdminLayout from '../../Components/AdminLayout';
import Pager from '../../Components/Pager';
import { useLocale } from '../../hooks';

export default function Messages({ messages }) {
    const { t } = useLocale();

    return (
        <AdminLayout title={t('messages')}>
            <Head title={t('messages')} />
            {messages.data.map((message) => (
                <article key={message.id} className="summary" style={{ marginBottom: 12 }}>
                    <strong>{message.name}</strong>
                    <p className="muted">{message.email} · {message.phone} · {message.created_at}</p>
                    <p>{message.body}</p>
                    {message.read_at
                        ? <span className="muted">{message.read_at}</span>
                        : <button type="button" className="text-button" onClick={() => router.patch(`/admin/messages/${message.id}`)}>{t('markRead')}</button>}
                </article>
            ))}
            <Pager paginator={messages} />
        </AdminLayout>
    );
}
