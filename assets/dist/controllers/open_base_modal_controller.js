var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/open_base_modal_controller.js
import { Controller } from "@hotwired/stimulus";
var open_base_modal_controller_default = class extends Controller {
  connect() {
    this.openModal = () => {
      window.dispatchEvent(new CustomEvent("base-modal:open", {
        detail: {
          modalSize: this.modalSizeValue,
          modalTitle: this.modalTitleValue,
          visitUrl: this.visitUrlValue,
          redirectOnSuccessToUrl: this.redirectOnSuccessToUrlValue,
          redirectOnSuccessToFrame: this.redirectOnSuccessToFrameValue,
          closeOnSuccess: this.closeOnSuccessValue
        }
      }));
    };
    this.element.addEventListener("click", this.openModal);
  }
  disconnect() {
    this.element.removeEventListener("click", this.openModal);
  }
};
__publicField(open_base_modal_controller_default, "values", {
  modalSize: { type: String, default: null },
  modalTitle: { type: String, default: null },
  visitUrl: { type: String, default: null },
  redirectOnSuccessToUrl: { type: String, default: null },
  redirectOnSuccessToFrame: { type: String, default: null },
  closeOnSuccess: { type: Boolean, default: true }
});
export {
  open_base_modal_controller_default as default
};
