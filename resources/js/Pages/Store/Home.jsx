import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Pager from '../../Components/Pager';
import StoreLayout from '../../Components/StoreLayout';
import { useLocale } from '../../hooks';
import { localized } from '../../i18n';

function Lines({ text }) {
    const lines = (text || '').split('\n');

    return lines.map((line, index) => (
        <span key={`${line}-${index}`}>
            {line}
            {index < lines.length - 1 && <br />}
        </span>
    ));
}

export default function Home({ filters, products, slides }) {
    const { settings, categories } = usePage().props;
    const { locale, t } = useLocale();
    const [index, setIndex] = useState(0);
    const [paused, setPaused] = useState(false);
    const [query, setQuery] = useState(filters.q ?? '');
    const current = categories.find((category) => category.slug === filters.category);

    useEffect(() => {
        setPaused(window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }, []);

    useEffect(() => {
        if (paused) {
            return undefined;
        }

        const timer = setInterval(() => setIndex((currentIndex) => (currentIndex + 1) % slides.length), 4500);

        return () => clearInterval(timer);
    }, [paused, slides.length]);

    return (
        <StoreLayout>
            <Head title="شيزين | SHEZAIN" />
            <section className="hero">
                <div className="hero-copy">
                    <span className="eyebrow">{t('kicker')}</span>
                    <h1><Lines text={settings[`hero_title_${locale}`]} /></h1>
                    <p><Lines text={settings[`hero_intro_${locale}`]} /></p>
                    <a className="primary" href="#collection">{settings[`hero_button_${locale}`]}</a>
                    <div className="hero-foot">{settings[`hero_foot_${locale}`]}</div>
                </div>
                <div className="hero-image">
                    <div className="hero-slides">
                        {slides.map((slide, slideIndex) => (
                            <img
                                key={slide.src}
                                className={slideIndex === index ? 'hero-slide is-active' : 'hero-slide'}
                                src={slide.src}
                                alt={slideIndex === index ? (locale === 'ar' ? 'صورة توضيحية لأناقة العبايات' : 'Illustrative abaya photograph') : ''}
                            />
                        ))}
                    </div>
                    <div className="slider-controls">
                        <button type="button" aria-label={paused ? t('playLabel') : t('pauseLabel')} onClick={() => setPaused((value) => !value)}>
                            {paused ? t('play') : t('pause')}
                        </button>
                        <div className="slider-dots">
                            {slides.map((slide, slideIndex) => (
                                <button
                                    key={`${slide.src}-${slideIndex}`}
                                    type="button"
                                    className={slideIndex === index ? 'selected' : ''}
                                    aria-label={`${t('slide')} ${slideIndex + 1}`}
                                    aria-pressed={slideIndex === index}
                                    onClick={() => setIndex(slideIndex)}
                                />
                            ))}
                        </div>
                    </div>
                    <div className="image-label">
                        {slides[index]?.label}
                        <span>{String(index + 1).padStart(2, '0')} / SHEZAIN</span>
                    </div>
                </div>
                <div className="vertical">{t('vertical')}</div>
            </section>

            <section className="collection" id="collection">
                <div className="section-heading">
                    <div>
                        <span className="eyebrow">{settings[`collection_kicker_${locale}`]}</span>
                        <h2>{current ? localized(locale, current, 'name') : t('collection')}</h2>
                    </div>
                </div>
                <div className="filters" aria-label={t('navLabel')}>
                    <Link href="/?category=all" className={filters.category === 'all' ? 'active' : ''} preserveScroll>{t('all')}</Link>
                    {categories.map((category) => (
                        <Link
                            key={category.slug}
                            href={`/?category=${category.slug}`}
                            className={filters.category === category.slug ? 'active' : ''}
                            preserveScroll
                        >
                            {localized(locale, category, 'name')}
                        </Link>
                    ))}
                </div>
                <form
                    className="search-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        router.get('/', { category: filters.category, q: query }, { preserveState: true, preserveScroll: true });
                    }}
                >
                    <label className="field" style={{ flex: 1 }}>
                        <span className="sr-only">{t('search')}</span>
                        <input value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t('search')} aria-label={t('search')} />
                    </label>
                    <button className="primary" type="submit">{t('search')}</button>
                </form>
                <p className="sample-note">{t('note')}</p>
                <p className="sample-note">{t('sample')}</p>
                {products.data.length === 0 ? <p>{t('empty')}</p> : (
                    <div className="products">
                        {products.data.map((product) => (
                            <Link className="product" key={product.slug} href={`/products/${product.slug}`}>
                                <div className="product-image">
                                    {product.image && <img src={product.image} alt="" />}
                                    <span className="tag">{localized(locale, product.category, 'name')}</span>
                                </div>
                                <h3>{localized(locale, product, 'name')}</h3>
                                <div className="product-meta">
                                    <span className="card-options">
                                        <span className="sr-only">{(product.colors ?? []).map((color) => localized(locale, color, 'color')).join(' · ')}</span>
                                        {(product.colors ?? []).map((color) => (
                                            <span key={color.color_ar} className="swatch" style={color.swatch ? { background: color.swatch } : undefined} />
                                        ))}
                                        <span>{product.sizes.join(' · ')}</span>
                                    </span>
                                    <span className="price">{product.price_formatted} {t('price')}</span>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
                <Pager paginator={products} />
            </section>

            <section className="brand-note">
                <span>◆</span>
                <h2>{settings[`brand_title_${locale}`]}</h2>
                <p>{settings[`brand_text_${locale}`]}</p>
            </section>
        </StoreLayout>
    );
}
