var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/tooltip_controller.js
import { Controller } from "@hotwired/stimulus";
import * as Bootstrap from "bootstrap";
var tooltip_controller_default = class extends Controller {
  connect() {
    this.hideTooltip = this.hideTooltip.bind(this);
    this.tooltip = Bootstrap.Tooltip.getOrCreateInstance(this.element, { title: this.titleValue, placement: this.placementValue });
    this.element.addEventListener("mouseleave", this.hideTooltip);
    this.element.addEventListener("click", this.hideTooltip);
  }
  hideTooltip() {
    this.tooltip.hide();
  }
  disconnect() {
    this.element.removeEventListener("mouseleave", this.hideTooltip);
    this.element.removeEventListener("click", this.hideTooltip);
    this.tooltip?.dispose();
  }
};
__publicField(tooltip_controller_default, "values", {
  title: String,
  placement: { type: String, default: "bottom" }
});
export {
  tooltip_controller_default as default
};
