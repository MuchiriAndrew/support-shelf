const escapeHtml = (value = '') => value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const escapeAttribute = (value = '') => escapeHtml(value).replaceAll('`', '&#96;');

const formatInline = (value = '') => {
    let escaped = escapeHtml(value);

    escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, (_, label, url) => {
        return `<a href="${escapeAttribute(url)}" target="_blank" rel="noreferrer" class="chat-link">${label}</a>`;
    });

    escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    escaped = escaped.replace(/__(.+?)__/g, '<strong>$1</strong>');
    escaped = escaped.replace(/(^|[\s(])\*(?!\*)([^*]+)\*(?=[\s).,!?:;]|$)/g, '$1<em>$2</em>');
    escaped = escaped.replace(/(^|[\s(])_(?!_)([^_]+)_(?=[\s).,!?:;]|$)/g, '$1<em>$2</em>');
    escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

    return escaped;
};

const wrapParagraph = (lines) => {
    if (lines.length === 0) {
        return '';
    }

    return `<p>${formatInline(lines.join(' '))}</p>`;
};

export const formatMessageContent = (value = '') => {
    const lines = String(value).replace(/\r\n/g, '\n').split('\n');
    let html = '';
    let paragraph = [];
    let listType = null;
    let inCodeBlock = false;
    let codeLines = [];

    const flushParagraph = () => {
        html += wrapParagraph(paragraph);
        paragraph = [];
    };

    const closeList = () => {
        if (listType !== null) {
            html += listType === 'ol' ? '</ol>' : '</ul>';
            listType = null;
        }
    };

    const flushCode = () => {
        if (codeLines.length > 0) {
            html += `<pre><code>${escapeHtml(codeLines.join('\n'))}</code></pre>`;
            codeLines = [];
        }
    };

    lines.forEach((rawLine) => {
        const line = rawLine.trimEnd();

        if (line.startsWith('```')) {
            flushParagraph();
            closeList();

            if (inCodeBlock) {
                flushCode();
            }

            inCodeBlock = ! inCodeBlock;
            return;
        }

        if (inCodeBlock) {
            codeLines.push(rawLine);
            return;
        }

        if (line.trim() === '') {
            flushParagraph();
            closeList();
            return;
        }

        const heading = line.match(/^#{1,3}\s+(.+)$/);

        if (heading) {
            flushParagraph();
            closeList();
            html += `<p><strong>${formatInline(heading[1])}</strong></p>`;
            return;
        }

        const unordered = line.match(/^[-*]\s+(.+)$/);

        if (unordered) {
            flushParagraph();

            if (listType !== 'ul') {
                closeList();
                html += '<ul>';
                listType = 'ul';
            }

            html += `<li>${formatInline(unordered[1])}</li>`;
            return;
        }

        const ordered = line.match(/^\d+\.\s+(.+)$/);

        if (ordered) {
            flushParagraph();

            if (listType !== 'ol') {
                closeList();
                html += '<ol>';
                listType = 'ol';
            }

            html += `<li>${formatInline(ordered[1])}</li>`;
            return;
        }

        const blockquote = line.match(/^>\s+(.+)$/);

        if (blockquote) {
            flushParagraph();
            closeList();
            html += `<blockquote>${formatInline(blockquote[1])}</blockquote>`;
            return;
        }

        paragraph.push(line.trim());
    });

    if (inCodeBlock) {
        flushCode();
    }

    flushParagraph();
    closeList();

    return html || '<p></p>';
};
