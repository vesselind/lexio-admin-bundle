var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/sortable_controller.js
import { Controller } from "@hotwired/stimulus";
import Sortable from "sortablejs";

// assets/src/flash.js
import { renderStreamMessage } from "@hotwired/turbo";
function flash(message, level = "success", url) {
  if (!url) {
    return Promise.reject(new Error("A flash endpoint URL must be configured."));
  }
  return fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      Accept: "text/vnd.turbo-stream.html",
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ level, message })
  }).then((response) => response.text()).then((html) => renderStreamMessage(html));
}

// assets/src/controllers/sortable_controller.js
var sortable_controller_default = class extends Controller {
  connect() {
    this.sortable = Sortable.create(this.element, {
      ghostClass: "blue-background-during-drag",
      animation: 300,
      onEnd: (event) => {
        const currentItemId = event.item.dataset.id;
        fetch(this.updatePositionUrlValue, {
          method: "POST",
          body: new URLSearchParams({
            currentItemId,
            newIndex: event.newIndex,
            oldIndex: event.oldIndex
          })
          //on success
        }).then((response) => {
          if (response.ok) {
            response.json().then((data) => {
              if (data.message && this.flashUrlValue) {
                flash(data.message, "success", this.flashUrlValue);
              }
            });
          } else {
            response.json().then((data) => {
              if (data.message && this.flashUrlValue) {
                flash(data.message, "error", this.flashUrlValue);
              }
            });
          }
        });
      }
    });
  }
  disconnect() {
    this.sortable?.destroy();
    this.sortable = null;
  }
};
__publicField(sortable_controller_default, "values", {
  updatePositionUrl: String,
  flashUrl: String
});
export {
  sortable_controller_default as default
};
