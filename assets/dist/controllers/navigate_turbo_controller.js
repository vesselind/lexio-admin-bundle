var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/navigate_turbo_controller.js
import { Controller } from "@hotwired/stimulus";
import { visit } from "@hotwired/turbo";
var navigate_turbo_controller_default = class extends Controller {
  connect() {
    this.navigate = this.navigate.bind(this);
    if (this.navigateOnLoadValue) {
      this.navigate();
    }
    this.element.addEventListener("click", this.navigate);
  }
  navigate() {
    visit(this.urlValue, { frame: this.frameIdValue });
  }
  disconnect() {
    this.element.removeEventListener("click", this.navigate);
  }
};
__publicField(navigate_turbo_controller_default, "values", {
  url: String,
  frameId: String,
  navigateOnLoad: { type: Boolean, default: false }
});
export {
  navigate_turbo_controller_default as default
};
