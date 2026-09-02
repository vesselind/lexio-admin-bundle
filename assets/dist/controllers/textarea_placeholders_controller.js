var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/textarea_placeholders_controller.js
import { Controller } from "@hotwired/stimulus";

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

// assets/src/controllers/textarea_placeholders_controller.js
var textarea_placeholders_controller_default = class extends Controller {
  insertText(event) {
    const currentText = this.textareaTarget.value;
    const cursorPosition = this.textareaTarget.selectionStart;
    this.textareaTarget.value = currentText.slice(0, cursorPosition) + "{{ " + event.target.textContent.trim() + " }}" + currentText.slice(cursorPosition);
    if (this.successMessageValue && this.flashUrlValue) {
      flash(this.successMessageValue, "success", this.flashUrlValue);
    }
  }
};
__publicField(textarea_placeholders_controller_default, "values", {
  successMessage: { type: String, default: null },
  flashUrl: { type: String, default: null }
});
__publicField(textarea_placeholders_controller_default, "targets", ["textarea"]);
export {
  textarea_placeholders_controller_default as default
};
