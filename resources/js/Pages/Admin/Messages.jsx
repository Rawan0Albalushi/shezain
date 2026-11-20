import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AdminLayout from '../../Components/AdminLayout';
import { CheckIcon } from '../../Components/AdminIcons';
import { applyQuery, IconButton, Ledger, Sheet, StatusMark } from '../../Components/AdminUi';
import Pager from '../../Components/Pager';
import { useLocale } from '../../hooks';

export default function Messages({ messages, filters, counts }) {
    const { t } = useLocale();
    const [q, setQ] = useState(filters.q ?? '');

    function apply(patch) {
        applyQuery('/admin/messages', { ...filters, q }, patch);
    }

    return (
        <AdminLayout title={t('messages')} lede={t('messagesIntro')}>
            <Ledger
                items={[
                    { key: 'all', value: counts.all, label: t('allMessages'), href: '/admin/messages', current: !filters.state && !filters.q },
                    { key: 'unread', value: counts.unread, label: t('unread'), href: '/admin/messages?state=unread', current: filters.state === 'unread' },
                    { key: 'read', value: counts.read, label: t('readMessages'), href: '/admin/messages?state=read', current: filters.state === 'read' },
                ]}
            />
            <form
                className="toolbar"
                onSubmit={(event) => {
                    event.preventDefault();
                    apply({ q });
                }}
            >
                <label className="field field-search">
                    <span>{t('find')}</span>
                    <input value={q} onChange={(event) => setQ(event.target.value)} placeholder={t('searchMessages')} />
                </label>
                <label className="field">
                    <span>{t('status')}</span>
                    <select value={filters.state ?? ''} onChange={(event) => apply({ state: event.target.value })}>
                        <option value="">{t('every')}</option>
                        <option value="unread">{t('unread')}</option>
                        <option value="read">{t('readMessages')}</option>
                    </select>
                </label>
                <div className="toolbar-actions">
                    <button className="primary" type="submit">{t('apply')}</button>
                    <Link className="quiet" href="/admin/messages">{t('clearFilters')}</Link>
                </div>
            </form>
            <Sheet title={t('messages')} detail={`${messages.total} ${t('results')}`}>
                <div className="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>{t('name')}</th>
                                <th>{t('reach')}</th>
                                <th>{t('message')}</th>
                                <th>{t('when')}</th>
                                <th>{t('status')}</th>
                                <th>{t('actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {messages.data.length === 0 && (
                                <tr>
                                    <td className="empty-row" colSpan={6}>{t('noMatches')}</td>
                                </tr>
                            )}
                            {messages.data.map((message) => (
                                <tr key={message.id} className={message.read_at ? undefined : 'is-unread'}>
                                    <td>{message.name}</td>
                                    <td>
                                        {message.email}
                                        {message.phone && <div className="sub">{message.phone}</div>}
                                    </td>
                                    <td><p className="clamp">{message.body}</p></td>
                                    <td className="num">{message.created_at}</td>
                                    <td>
                                        <StatusMark status={message.read_at ? 'read' : 'unread'} label={message.read_at ? t('readMessages') : t('unread')} />
                                        {message.read_at && <div className="sub">{message.read_at}</div>}
                                    </td>
                                    <td className="actions-cell">
                                        {!message.read_at && (
                                            <IconButton
                                                label={t('markRead')}
                                                onClick={() => router.patch(`/admin/messages/${message.id}`, {}, { preserveScroll: true })}
                                            >
                                                <CheckIcon />
                                            </IconButton>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Sheet>
            <Pager paginator={messages} />
        </AdminLayout>
    );
}
