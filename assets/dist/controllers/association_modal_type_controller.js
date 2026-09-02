var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/association_modal_type_controller.js
import { Controller } from "@hotwired/stimulus";
var association_modal_type_controller_default = class extends Controller {
  connect() {
    this.updateAutocomplete = this.updateAutocomplete.bind(this);
    window.addEventListener("add-autocomplete-item:update", this.updateAutocomplete);
  }
  updateAutocomplete(event) {
    if (event.detail.class !== this.classValue) {
      return;
    }
    this.tomSelectControl = this.element.querySelector("select").tomselect;
    this.tomSelectControl.addOption({
      value: event.detail.itemId,
      text: event.detail.itemName
    });
    this.tomSelectControl.addItem(event.detail.itemId);
  }
  disconnect() {
    window.removeEventListener("add-autocomplete-item:update", this.updateAutocomplete);
  }
};
__publicField(association_modal_type_controller_default, "values", {
  class: String
});
__publicField(association_modal_type_controller_default, "targets", ["selectItem"]);
export {
  association_modal_type_controller_default as default
};
