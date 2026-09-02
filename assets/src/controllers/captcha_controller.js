import { Controller } from '@hotwired/stimulus';

let scriptPromise;

export default class extends Controller {
    static values = {
        siteKey: String,
        action: {
            type: String,
            default: 'submit',
        },
        scriptUrl: {
            type: String,
            default: 'https://www.google.com/recaptcha/enterprise.js',
        },
    };

    connect() {
        this.element.value = '';
        this.form = this.element.closest('form');
        this.isRefreshing = false;
        this.isResubmitting = false;
        this.onSubmit = this.refreshTokenAndSubmit.bind(this);

        if (!this.siteKeyValue || !this.form) {
            return;
        }

        this.loadRecaptcha().catch(() => {
            if (this.element.isConnected) {
                this.element.value = '';
            }
        });
        this.form.addEventListener('submit', this.onSubmit, true);
    }

    disconnect() {
        this.form?.removeEventListener('submit', this.onSubmit, true);
    }

    async refreshTokenAndSubmit(event) {
        if (this.isResubmitting) {
            return;
        }

        event.preventDefault();

        if (this.isRefreshing) {
            return;
        }

        this.isRefreshing = true;

        try {
            const token = await this.execute();

            if (!token || !this.element.isConnected || !this.form?.isConnected) {
                return;
            }

            this.element.value = token;
            this.isResubmitting = true;
            this.form.requestSubmit(event.submitter);
        } catch {
            if (this.element.isConnected) {
                this.element.value = '';
            }
        } finally {
            this.isResubmitting = false;
            this.isRefreshing = false;
        }
    }

    loadRecaptcha() {
        if (window.grecaptcha?.enterprise) {
            return Promise.resolve(window.grecaptcha);
        }

        if (scriptPromise) {
            return scriptPromise;
        }

        scriptPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-google-recaptcha-enterprise]');

            if (existingScript) {
                this.waitForScript(existingScript, resolve, reject);

                return;
            }

            const script = document.createElement('script');
            const scriptUrl = new URL(this.scriptUrlValue, document.baseURI);
            scriptUrl.searchParams.set('render', this.siteKeyValue);
            script.src = scriptUrl.toString();
            script.async = true;
            script.defer = true;
            script.dataset.googleRecaptchaEnterprise = 'loading';
            this.waitForScript(script, resolve, reject);
            document.head.appendChild(script);
        });

        return scriptPromise;
    }

    waitForScript(script, resolve, reject) {
        if (script.dataset.googleRecaptchaEnterprise === 'ready') {
            resolve(window.grecaptcha);

            return;
        }

        script.addEventListener('load', () => {
            script.dataset.googleRecaptchaEnterprise = 'ready';
            resolve(window.grecaptcha);
        }, { once: true });
        script.addEventListener('error', () => {
            scriptPromise = undefined;
            script.remove();
            reject(new Error('Google reCAPTCHA Enterprise could not be loaded.'));
        }, { once: true });
    }

    async execute() {
        const recaptcha = await this.loadRecaptcha();

        if (!recaptcha?.enterprise) {
            throw new Error('Google reCAPTCHA Enterprise is not available.');
        }

        await new Promise((resolve) => recaptcha.enterprise.ready(resolve));

        return recaptcha.enterprise.execute(this.siteKeyValue, { action: this.actionValue });
    }
}
