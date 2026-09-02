var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/image_gallery_controller.js
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

// assets/src/controllers/image_gallery_controller.js
import { visit } from "@hotwired/turbo";
var image_gallery_controller_default = class extends Controller {
  selectImage(event) {
    const imageId = event.params.imageId;
    const imagePath = event.params.imagePath;
    this.dispatch("image-selected", {
      detail: {
        imageId,
        imagePath
      }
    });
    window.dispatchEvent(new CustomEvent("base-modal:close"));
  }
  uploadImageSubmit(e) {
    e.preventDefault();
    const input = this.hasFileTarget ? this.fileTarget : document.getElementById("file");
    const image = input?.files?.[0];
    if (!image) {
      return;
    }
    const formData = new FormData();
    formData.append("file", image);
    fetch(this.uploadUrlValue, { method: "POST", body: formData }).then((response) => response.json().then((data) => ({ response, data }))).then(({ response, data }) => {
      const flashType = response.ok ? "success" : "error";
      if (data.message && this.flashUrlValue) {
        flash(data.message, flashType, this.flashUrlValue);
      }
      visit(this.currentUrlValue, { frame: this.currentFrameValue });
    });
  }
};
__publicField(image_gallery_controller_default, "values", {
  uploadUrl: String,
  currentUrl: String,
  currentFrame: String,
  flashUrl: String
});
__publicField(image_gallery_controller_default, "targets", ["file"]);
export {
  image_gallery_controller_default as default
};
