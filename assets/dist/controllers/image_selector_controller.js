// assets/src/controllers/image_selector_controller.js
import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
var image_selector_controller_default = class extends Controller {
  async initialize() {
    this.component = await getComponent(this.element);
  }
  selectImage(event) {
    const imageId = event.params.imageId;
    this.component.action("setImagePath", { imageId: event.detail.imageId });
  }
};
export {
  image_selector_controller_default as default
};
