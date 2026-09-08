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
  }
  toggle(event) {
    event.preventDefault();
    event.stopPropagation();
    const targetSelector = event.currentTarget?.getAttribute("data-bs-target");
    const targetId = targetSelector?.startsWith("#") ? targetSelector.slice(1) : null;
    if (!targetId || !this.bsCollapse?.[targetId]) {
      return;
    }
    if (this.singleOpenValue) {
      Object.entries(this.bsCollapse).forEach(([id, collapse]) => {
        if (id !== targetId) {
          collapse.hide();
        }
      });
    }
    this.bsCollapse[targetId].toggle();
  }
  disconnect() {
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
