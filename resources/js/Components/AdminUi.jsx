import { Link, router } from '@inertiajs/react';

export function applyQuery(url, current, patch) {
    const params = { ...current, ...patch };
    const query = {};

    Object.entries(params).forEach(([key, value]) => {
        if (value !== '' && value != null) {
            query[key] = value;
        }
    });

    router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
}

export function Ledger({ items }) {
    return (
        <div className="ledger">
            {items.map((item) => {
                const className = item.current ? 'stat-card is-current' : 'stat-card';
                const body = (
                    <>
                        <span>{item.label}</span>
                        <b>{item.value}</b>
                    </>
                );

                if (!item.href) {
                    return (
                        <div key={item.key} className={className}>
                            {body}
                        </div>
                    );
                }

                return (
                    <Link key={item.key} href={item.href} className={className} preserveScroll>
                        {body}
                    </Link>
                );
            })}
        </div>
    );
}

export function Sheet({ title, detail, action, children }) {
    return (
        <section className="sheet">
            {(title || action) && (
                <header className="sheet-head">
                    <div>
                        {title && <h2>{title}</h2>}
                        {detail && <p>{detail}</p>}
                    </div>
                    {action}
                </header>
            )}
            {children}
        </section>
    );
}

export function IconButton({ label, href, method, onClick, disabled, children }) {
    const content = (
        <>
            {children}
            <span className="tip">{label}</span>
        </>
    );

    if (href) {
        return (
            <Link href={href} method={method} className="icon-button" aria-label={label} preserveScroll>
                {content}
            </Link>
        );
    }

    return (
        <button type="button" className="icon-button" aria-label={label} onClick={onClick} disabled={disabled}>
            {content}
        </button>
    );
}

export function StatusMark({ status, label }) {
    return <span className={`status status-${status}`}>{label}</span>;
}
