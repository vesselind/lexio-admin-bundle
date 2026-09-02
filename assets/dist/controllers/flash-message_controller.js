var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/flash-message_controller.js
import { Controller } from "@hotwired/stimulus";
var FLASH_DURATION = 5e3;
var flash_message_controller_default = class extends Controller {
  connect() {
    this.timerFrame = requestAnimationFrame(() => {
      if (this.hasTimerTarget) {
        this.timerTarget.style.width = "0%";
      }
    });
    this.timeout = setTimeout(() => {
      this.element.classList.add("d-none");
    }, FLASH_DURATION);
  }
  close() {
    this.element.classList.add("d-none");
  }
  disconnect() {
    clearTimeout(this.timeout);
    cancelAnimationFrame(this.timerFrame);
  }
};
__publicField(flash_message_controller_default, "targets", ["timer"]);
export {
  flash_message_controller_default as default
};
