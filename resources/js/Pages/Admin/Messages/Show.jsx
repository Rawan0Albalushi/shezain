import AdminLayout from '../../../Components/AdminLayout';
import { Sheet, StatusMark } from '../../../Components/AdminUi';
import { useLocale } from '../../../hooks';

export default function Show({ message }) {
    const { t } = useLocale();

    return (
        <AdminLayout
            title={message.name}
            lede={message.created_at}
            back={{ href: '/admin/messages', label: t('backToMessages') }}
            actions={<StatusMark status={message.read_at ? 'read' : 'unread'} label={message.read_at ? t('readMessages') : t('unread')} />}
        >
            <Sheet title={t('reach')}>
                <dl className="meta">
                    <dt>{t('name')}</dt>
                    <dd>{message.name}</dd>
                    <dt>{t('email')}</dt>
                    <dd>{message.email}</dd>
                    {message.phone && (
                        <>
                            <dt>{t('phone')}</dt>
                            <dd className="num">{message.phone}</dd>
                        </>
                    )}
                    <dt>{t('when')}</dt>
                    <dd className="num">{message.created_at}</dd>
                    {message.read_at && (
                        <>
                            <dt>{t('readAt')}</dt>
                            <dd className="num">{message.read_at}</dd>
                        </>
                    )}
                </dl>
            </Sheet>
            <Sheet title={t('message')}>
                <p className="admin-prose">{message.body}</p>
            </Sheet>
        </AdminLayout>
    );
}
