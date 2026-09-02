import { Controller } from '@hotwired/stimulus';
import { Datepicker } from 'vanillajs-datepicker';
import locales from '../vanilla-datepicker-locales';
import '../styles/vanilla_datepicker.scss';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {
        format: {type: String, default: 'dd/mm/yyyy'},
        weekStart: {type: String, default: '0'},
        minDate: {type: String, default: null},
        maxDate: {type: String, default: null},
        locale: {type: String, default: 'en'},
        clicked: Boolean
    }

    connect() {
        Object.assign(Datepicker.locales, locales, globalThis.LexioAdminDatepickerLocales ?? {});

        this.datepicker = new Datepicker(this.element, {
            buttonClass: 'btn',
            autohide: true,
            format: this.formatValue,
            weekStart: this.weekStartValue,
            minDate: this.minDateValue,
            maxDate: this.maxDateValue,
            language: this.localeValue,
        });
    }

    disconnect() {
        this.datepicker?.destroy();
        this.datepicker = null;
    }
}
