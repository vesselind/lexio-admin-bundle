var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/bulk_action_controller.js
import { Controller } from "@hotwired/stimulus";
var bulk_action_controller_default = class extends Controller {
  connect() {
    this.checkedItemsIds = [];
    window.addEventListener("check-selection:itemsUpdated", this.updateItems.bind(this));
  }
  updateItems(event) {
    this.checkedItemsIds = event.detail;
    for (let button of this.buttonTargets) {
      if (this.checkedItemsIds.length > 0) {
        button.classList.remove("d-none");
      } else {
        button.classList.add("d-none");
      }
    }
  }
  openConfirmationModal(event) {
    this.url = event.params.url;
    this.modalBodyTarget.innerHTML = this.modalBodyTarget.innerHTML.replaceAll("%action%", event.currentTarget.innerText.trim());
    this.modalBodyTarget.innerHTML = this.modalBodyTarget.innerHTML.replaceAll("%count%", this.checkedItemsIds.length);
  }
  confirm(event) {
    event.preventDefault();
    const searchParams = new URLSearchParams();
    searchParams.set("entityFqcn", this.entityFqcnValue);
    this.checkedItemsIds.forEach((id) => searchParams.append("ids[]", id));
    const url = `${this.url}?${searchParams.toString()}`;
    fetch(url, {
      method: "POST",
      headers: {
        "X-CSRF-Token": this.csrfTokenValue,
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then((response) => {
      window.location.href = response.url;
    });
  }
};
__publicField(bulk_action_controller_default, "values", {
  entityFqcn: String,
  csrfToken: String
});
__publicField(bulk_action_controller_default, "targets", ["button", "modalBody"]);
export {
  bulk_action_controller_default as default
};
