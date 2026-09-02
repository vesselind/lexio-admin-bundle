var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/clipboard_controller.js
import { Controller } from "@hotwired/stimulus";
var clipboard_controller_default = class extends Controller {
  copy(event) {
    event.preventDefault();
    const text = this.sourceTarget?.innerHTML || this.sourceTarget.value;
    navigator.clipboard.writeText(text).then(() => this.copied());
  }
  copied() {
  }
};
__publicField(clipboard_controller_default, "targets", ["source"]);
export {
  clipboard_controller_default as default
};
