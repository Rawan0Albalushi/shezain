export default function Pager({ paginator }) {
    if (!paginator?.links || paginator.last_page < 2) {
        return null;
    }

    return (
        <nav className="pager" aria-label="Pagination">
            {paginator.links.map((link, index) => (
                link.url
                    ? <a key={index} href={link.url} className={link.active ? 'active' : ''} dangerouslySetInnerHTML={{ __html: link.label }} />
                    : <span key={index} dangerouslySetInnerHTML={{ __html: link.label }} />
            ))}
        </nav>
    );
}
