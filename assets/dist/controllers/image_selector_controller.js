// assets/src/controllers/image_selector_controller.js
import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";
var image_selector_controller_default = class extends Controller {
  async initialize() {
    this.component = await getComponent(this.element);
  }
  async selectImage(event) {
    const imageId = event.detail?.imageId;
    if (!imageId || !this.component) {
      return;
    }
    await this.component.action("setImage", { imageId });
  }
};
export {
  image_selector_controller_default as default
};
