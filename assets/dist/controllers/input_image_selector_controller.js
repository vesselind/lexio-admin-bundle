// assets/src/controllers/input_image_selector_controller.js
import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
var input_image_selector_controller_default = class extends Controller {
  connect() {
    this.listenForImageSelection = false;
    this.enableImageSelection = () => {
      this.listenForImageSelection = true;
    };
    this.element.addEventListener("click", this.enableImageSelection);
  }
  selectImage(event) {
    if (!this.listenForImageSelection) {
      return;
    }
    this.element.value = event.detail.imagePath;
    this.listenForImageSelection = false;
  }
  disconnect() {
    this.element.removeEventListener("click", this.enableImageSelection);
  }
};
export {
  input_image_selector_controller_default as default
};
