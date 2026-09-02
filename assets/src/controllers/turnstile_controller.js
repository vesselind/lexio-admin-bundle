import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = {
        apiKey: String,
        scriptUrl: {
            type: String,
            default: 'https://challenges.cloudflare.com/turnstile/v0/api.js',
        },
    };

    connect() {
        if (window.turnstile) {
            this.renderTurnstile();
        } else {
            this.loadTurnstileScript();
        }
    }

    loadTurnstileScript() {
        const existingScript = document.querySelector(`script[src="${this.scriptUrlValue}"]`);
        if (existingScript) {
            existingScript.addEventListener('load', () => this.renderTurnstile(), { once: true });
            return;
        }

        const script = document.createElement("script");
        script.src = this.scriptUrlValue;
        script.async = true;
        script.defer = true;
        script.onload = () => this.renderTurnstile();

        document.head.appendChild(script);
    }

    renderTurnstile() {
        if (!window.turnstile) {
            return;
        }

        window.turnstile.render(this.element, {
            sitekey: this.apiKeyValue || this.element.getAttribute('data-api-key'),
            callback: (token) => this.onSuccess(token),
        });
    }

    onSuccess(token) {
        if (this.element) {
            this.element.value = token;
        }
    }
}
