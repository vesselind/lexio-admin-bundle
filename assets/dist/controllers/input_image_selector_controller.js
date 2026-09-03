var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/input_image_selector_controller.js
import { Controller } from "@hotwired/stimulus";
var input_image_selector_controller_default = class extends Controller {
  connect() {
    this.listenForImageSelection = false;
    this.enableImageSelection = () => {
      if (!this.hasInputTarget || this.inputTarget.disabled) {
        return;
      }
      this.listenForImageSelection = true;
    };
    this.element.addEventListener("click", this.enableImageSelection);
  }
  selectImage(event) {
    const imagePath = event.detail?.imagePath;
    if (!this.listenForImageSelection || !imagePath) {
      return;
    }
    const imageName = event.detail?.imageName || this.fileNameFromPath(imagePath);
    this.inputTarget.value = imagePath;
    if (this.hasPreviewTarget) {
      this.previewTarget.src = imagePath;
      this.previewTarget.alt = imageName;
    }
    if (this.hasPreviewContainerTarget) {
      this.previewContainerTarget.hidden = false;
    }
    if (this.hasEmptyStateTarget) {
      this.emptyStateTarget.hidden = true;
    }
    if (this.hasFileNameTarget) {
      this.fileNameTarget.textContent = imageName;
      this.fileNameTarget.hidden = !imageName;
    }
    if (this.hasCardTarget) {
      this.cardTarget.classList.remove("input-image-selector__card--empty");
      this.cardTarget.classList.add("input-image-selector__card--has-image");
    }
    this.inputTarget.dispatchEvent(new Event("input", { bubbles: true }));
    this.inputTarget.dispatchEvent(new Event("change", { bubbles: true }));
    this.listenForImageSelection = false;
  }
  fileNameFromPath(path) {
    const pathWithoutQuery = path.split(/[?#]/, 1)[0];
    const pathSegment = pathWithoutQuery.substring(pathWithoutQuery.lastIndexOf("/") + 1);
    try {
      return decodeURIComponent(pathSegment);
    } catch {
      return pathSegment;
    }
  }
  disconnect() {
    this.element.removeEventListener("click", this.enableImageSelection);
  }
};
__publicField(input_image_selector_controller_default, "targets", ["input", "card", "previewContainer", "preview", "emptyState", "fileName"]);
export {
  input_image_selector_controller_default as default
};
