function Glyph({ children }) {
    return (
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" strokeWidth="1.75" strokeLinecap="round" strokeLinejoin="round">
            {children}
        </svg>
    );
}

export function GridIcon() {
    return (
        <Glyph>
            <rect x="3.5" y="3.5" width="7" height="7" />
            <rect x="13.5" y="3.5" width="7" height="7" />
            <rect x="3.5" y="13.5" width="7" height="7" />
            <rect x="13.5" y="13.5" width="7" height="7" />
        </Glyph>
    );
}

export function TagIcon() {
    return (
        <Glyph>
            <path d="M20 13.2 12.8 20.4a2 2 0 0 1-2.8 0L3 13.4V4h9.4z" />
            <circle cx="8.2" cy="9.2" r="1.1" />
        </Glyph>
    );
}

export function ChartIcon() {
    return (
        <Glyph>
            <path d="M4 19.5h16" />
            <path d="M7 19.5v-6" />
            <path d="M12 19.5V6.5" />
            <path d="M17 19.5v-9" />
        </Glyph>
    );
}

export function ReceiptIcon() {
    return (
        <Glyph>
            <path d="M6 3.5h12v17l-2-1.4-2 1.4-2-1.4-2 1.4-2-1.4-2 1.4z" />
            <path d="M9 8h6M9 12h6M9 16h4" />
        </Glyph>
    );
}

export function TruckIcon() {
    return (
        <Glyph>
            <path d="M3 7.5h11v8H3z" />
            <path d="M14 11h3.5L20 14v1.5h-6" />
            <circle cx="7" cy="17.5" r="1.4" />
            <circle cx="17" cy="17.5" r="1.4" />
        </Glyph>
    );
}

export function MailIcon() {
    return (
        <Glyph>
            <rect x="3" y="5" width="18" height="14" />
            <path d="m4 7 8 6 8-6" />
        </Glyph>
    );
}

export function TypeIcon() {
    return (
        <Glyph>
            <path d="M5 6h14" />
            <path d="M12 6v12" />
            <path d="M8.5 18h7" />
        </Glyph>
    );
}

export function LogoutIcon() {
    return (
        <Glyph>
            <path d="M10 7V4.5H4.5v15H10V17" />
            <path d="M10 12h9" />
            <path d="m15.5 8.5 3.5 3.5-3.5 3.5" />
        </Glyph>
    );
}

export function EyeIcon() {
    return (
        <Glyph>
            <path d="M2.5 12S6.5 6.5 12 6.5 21.5 12 21.5 12 17.5 17.5 12 17.5 2.5 12 2.5 12z" />
            <circle cx="12" cy="12" r="2.4" />
        </Glyph>
    );
}

export function EyeOffIcon() {
    return (
        <Glyph>
            <path d="M4 4.5 20 19.5" />
            <path d="M9.8 6.4A9 9 0 0 1 12 6c5.5 0 9.5 6 9.5 6a16 16 0 0 1-3.1 3.6" />
            <path d="M6.2 7.4A16 16 0 0 0 2.5 12S6.5 18 12 18a9 9 0 0 0 3.6-.8" />
        </Glyph>
    );
}

export function PencilIcon() {
    return (
        <Glyph>
            <path d="M4 20h4L18.5 9.5 14.5 5.5 4 16z" />
            <path d="m13.2 6.8 4 4" />
        </Glyph>
    );
}

export function CheckIcon() {
    return (
        <Glyph>
            <path d="m5 12.5 4.5 4.5L19 7.5" />
        </Glyph>
    );
}

export function StoreIcon() {
    return (
        <Glyph>
            <path d="M4 10.5h16V20H4z" />
            <path d="M3 10.5 5.2 5h13.6L21 10.5" />
            <path d="M9 20v-5h6v5" />
        </Glyph>
    );
}

export function MenuIcon() {
    return (
        <Glyph>
            <path d="M4 7h16M4 12h16M4 17h16" />
        </Glyph>
    );
}

export function CloseIcon() {
    return (
        <Glyph>
            <path d="m6 6 12 12M18 6 6 18" />
        </Glyph>
    );
}
