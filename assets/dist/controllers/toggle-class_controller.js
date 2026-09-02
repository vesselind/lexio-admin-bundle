var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/toggle-class_controller.js
import { Controller } from "@hotwired/stimulus";
var toggle_class_controller_default = class extends Controller {
  toggle(e) {
    this.element.querySelectorAll(`.${this.classValue}`).forEach((el) => {
      el.classList.remove(this.classValue);
    });
    e.currentTarget.classList.add(this.classValue);
  }
};
__publicField(toggle_class_controller_default, "values", {
  class: String
});
export {
  toggle_class_controller_default as default
};
