var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/collapsable_sidebar_controller.js
import { Controller } from "@hotwired/stimulus";
import * as Bootstrap from "bootstrap";
var collapsable_sidebar_controller_default = class extends Controller {
  connect() {
    const collapseElements = Array.from(this.element.querySelectorAll(".dropdown-panel > .collapse"));
    this.bsCollapse = Object.fromEntries(collapseElements.map((element) => [
      element.id,
      new Bootstrap.Collapse(element, { toggle: false })
    ]));
    if (this.singleOpenValue) {
      this.closeOtherPanels = () => {
        collapseElements.forEach((element) => this.bsCollapse[element.id]?.hide());
      };
      this.element.querySelectorAll(".btn-toggle").forEach((item) => {
        item.addEventListener("click", this.closeOtherPanels);
      });
    }
  }
  disconnect() {
    if (this.closeOtherPanels) {
      this.element.querySelectorAll(".btn-toggle").forEach((item) => {
        item.removeEventListener("click", this.closeOtherPanels);
      });
    }
    Object.values(this.bsCollapse ?? {}).forEach((collapse) => collapse.dispose());
    this.bsCollapse = {};
  }
};
__publicField(collapsable_sidebar_controller_default, "values", {
  singleOpen: { type: Boolean, default: false }
});
export {
  collapsable_sidebar_controller_default as default
};
