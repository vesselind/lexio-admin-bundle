var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/check_selection_controller.js
import { Controller } from "@hotwired/stimulus";
var check_selection_controller_default = class extends Controller {
  connect() {
    this.checkedItems = [];
  }
  selectItem(event) {
    this.updateItemsCollection();
  }
  selectAll(event) {
    this.checkboxItemTargets.forEach((checkbox) => {
      checkbox.checked = event.target.checked;
    });
    this.updateItemsCollection();
  }
  updateItemsCollection() {
    this.checkboxItemTargets.forEach((checkbox) => {
      let isChecked = checkbox.checked;
      const itemId = checkbox.getAttribute("data-item-id");
      if (isChecked) {
        this.checkedItems.push(itemId);
      } else {
        this.checkedItems = this.checkedItems.filter((itemId2) => {
          return itemId2 !== checkbox.getAttribute("data-item-id");
        });
      }
      let closestTr = checkbox.closest("tr");
      if (closestTr) {
        closestTr.classList.toggle("selected", isChecked);
      }
    });
    this.checkedItems = [...new Set(this.checkedItems)];
    this.dispatch("itemsUpdated", { detail: this.checkedItems });
  }
};
__publicField(check_selection_controller_default, "targets", ["checkboxItem"]);
export {
  check_selection_controller_default as default
};
