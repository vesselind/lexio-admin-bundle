import { renderStreamMessage } from '@hotwired/turbo';

/**
 * Render a server-generated Turbo flash stream.
 *
 * The endpoint is deliberately supplied by the caller. The bundle cannot
 * assume the host application's route name or URL prefix.
 */
export default function flash(message, level = 'success', url) {
    if (!url) {
        return Promise.reject(new Error('A flash endpoint URL must be configured.'));
    }

    return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'text/vnd.turbo-stream.html',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({level, message}),
    })
        .then((response) => response.text())
        .then((html) => renderStreamMessage(html));
}
