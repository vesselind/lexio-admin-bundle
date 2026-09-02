import { Controller } from '@hotwired/stimulus';
import { Datepicker } from 'vanillajs-datepicker';
import locales from '../vanilla-datepicker-locales';
import '../styles/vanilla_datepicker.scss';
import {getComponent} from "@symfony/ux-live-component";

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {
        componentName: {type: String, default: null},
        format: {type: String, default: 'dd/mm/yyyy'},
        weekStart: {type: Number, default: 0},
    }

    async initialize() {
        const componentElement = this.componentNameValue
            ? document.querySelector(`[data-live-name-value="${this.componentNameValue}"]`)
            : this.element;
        this.component = await getComponent(componentElement);
    }

    connect() {
        Object.assign(Datepicker.locales, locales, globalThis.LexioAdminDatepickerLocales ?? {});

        this.datepicker = new Datepicker(this.element, {
            buttonClass: 'btn',
            autohide: true,
            format: this.formatValue,
            weekStart: this.weekStartValue
        });

        this.onDatepickerHide = () => {

            this.element.dispatchEvent(new Event('change', { bubbles: true }));
        };

        this.element.addEventListener('hide', this.onDatepickerHide);
    }

    disconnect() {
        this.element.removeEventListener('hide', this.onDatepickerHide);
        this.datepicker?.destroy();
        this.datepicker = null;
    }
}
