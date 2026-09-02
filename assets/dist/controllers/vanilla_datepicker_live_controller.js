var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/vanilla_datepicker_live_controller.js
import { Controller } from "@hotwired/stimulus";
import { Datepicker } from "vanillajs-datepicker";

// assets/src/vanilla-datepicker-locales.js
var vanilla_datepicker_locales_default = {};

// assets/src/controllers/vanilla_datepicker_live_controller.js
import { getComponent } from "@symfony/ux-live-component";
var vanilla_datepicker_live_controller_default = class extends Controller {
  async initialize() {
    const componentElement = this.componentNameValue ? document.querySelector(`[data-live-name-value="${this.componentNameValue}"]`) : this.element;
    this.component = await getComponent(componentElement);
  }
  connect() {
    Object.assign(Datepicker.locales, vanilla_datepicker_locales_default, globalThis.LexioAdminDatepickerLocales ?? {});
    this.datepicker = new Datepicker(this.element, {
      buttonClass: "btn",
      autohide: true,
      format: this.formatValue,
      weekStart: this.weekStartValue
    });
    this.onDatepickerHide = () => {
      this.element.dispatchEvent(new Event("change", { bubbles: true }));
    };
    this.element.addEventListener("hide", this.onDatepickerHide);
  }
  disconnect() {
    this.element.removeEventListener("hide", this.onDatepickerHide);
    this.datepicker?.destroy();
    this.datepicker = null;
  }
};
__publicField(vanilla_datepicker_live_controller_default, "values", {
  componentName: { type: String, default: null },
  format: { type: String, default: "dd/mm/yyyy" },
  weekStart: { type: Number, default: 0 }
});
export {
  vanilla_datepicker_live_controller_default as default
};
