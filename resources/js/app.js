// Editorial form interactions. Server-side validation and authorization remain authoritative.
const editor = document.querySelector('#article-form');

if (editor) {
    const title = editor.querySelector('#title');
    const slug = editor.querySelector('#slug');
    const body = editor.querySelector('#body');
    const preview = document.querySelector('#markdown-preview');
    const previewButton = document.querySelector('#preview-toggle');
    const previewError = document.querySelector('#preview-error');

    // Suggest a slug only for new, empty slugs. Never overwrite a user's choice.
    if (title && slug && !slug.value.trim()) {
        let manuallyEdited = false;
        slug.addEventListener('input', () => { manuallyEdited = true; });
        title.addEventListener('input', () => {
            if (manuallyEdited) return;
            slug.value = title.value.normalize('NFKD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 180)
                .replace(/-+$/g, '');
        });
    }

    const insertText = (before, after = before, placeholder = 'text') => {
        if (!body) return;
        const start = body.selectionStart;
        const end = body.selectionEnd;
        const selected = body.value.slice(start, end) || placeholder;
        body.setRangeText(before + selected + after, start, end, 'select');
        body.focus();
        body.setSelectionRange(start + before.length, start + before.length + selected.length);
        body.dispatchEvent(new Event('input', { bubbles: true }));
    };

    document.querySelectorAll('[data-markdown]').forEach((button) => {
        button.addEventListener('click', () => {
            switch (button.dataset.markdown) {
                case 'bold':
                    insertText('**');
                    break;
                case 'heading':
                    insertText('## ', '', 'Heading');
                    break;
                case 'link':
                    insertText('[', '](https://example.com)', 'link text');
                    break;
            }
        });
    });

    if (previewButton && preview && body) {
        previewButton.setAttribute('aria-controls', preview.id);
        previewButton.setAttribute('aria-expanded', 'false');
        previewButton.addEventListener('click', async () => {
            if (!preview.hidden) {
                preview.hidden = true;
                previewButton.setAttribute('aria-expanded', 'false');
                return;
            }
            previewError.textContent = '';
            previewButton.disabled = true;
            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(previewButton.dataset.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify({ body: body.value }),
                });
                if (!response.ok) throw new Error('Unable to generate preview.');
                const result = await response.json();
                // HTML is rendered by the server's Markdown sanitizer, not from raw input.
                preview.innerHTML = result.html;
                preview.hidden = false;
                previewButton.setAttribute('aria-expanded', 'true');
            } catch {
                previewError.textContent = 'Preview is temporarily unavailable. Please try again.';
            } finally {
                previewButton.disabled = false;
            }
        });
    }
}

document.querySelectorAll('form').forEach((form) => {
    const method = form.querySelector('input[name="_method"]')?.value?.toUpperCase();
    if (method === 'DELETE') {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Delete this item? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    }
});
