var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/add_autocomplete_item_controller.js
import { Controller } from "@hotwired/stimulus";
var add_autocomplete_item_controller_default = class extends Controller {
  connect() {
    this.dispatch("update", { detail: { class: this.classValue, itemId: this.itemIdValue, itemName: this.itemNameValue } });
  }
};
__publicField(add_autocomplete_item_controller_default, "values", {
  itemId: String,
  itemName: String,
  class: String
});
export {
  add_autocomplete_item_controller_default as default
};
