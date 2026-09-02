var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/vanilla_datepicker_controller.js
import { Controller } from "@hotwired/stimulus";
import { Datepicker } from "vanillajs-datepicker";

// assets/src/vanilla-datepicker-locales.js
var vanilla_datepicker_locales_default = {};

// assets/src/controllers/vanilla_datepicker_controller.js
var vanilla_datepicker_controller_default = class extends Controller {
  connect() {
    Object.assign(Datepicker.locales, vanilla_datepicker_locales_default, globalThis.LexioAdminDatepickerLocales ?? {});
    this.datepicker = new Datepicker(this.element, {
      buttonClass: "btn",
      autohide: true,
      format: this.formatValue,
      weekStart: this.weekStartValue,
      minDate: this.minDateValue,
      maxDate: this.maxDateValue,
      language: this.localeValue
    });
  }
  disconnect() {
    this.datepicker?.destroy();
    this.datepicker = null;
  }
};
__publicField(vanilla_datepicker_controller_default, "values", {
  format: { type: String, default: "dd/mm/yyyy" },
  weekStart: { type: String, default: "0" },
  minDate: { type: String, default: null },
  maxDate: { type: String, default: null },
  locale: { type: String, default: "en" },
  clicked: Boolean
});
export {
  vanilla_datepicker_controller_default as default
};
