import { Controller } from "@hotwired/stimulus"
import { getComponent } from '@symfony/ux-live-component';
export default class extends Controller {

    connect() {
        this.listenForImageSelection = false;

        this.enableImageSelection = () => {
            this.listenForImageSelection = true;
        };

        this.element.addEventListener('click', this.enableImageSelection);
    }

    selectImage(event) {
        if (!this.listenForImageSelection) {
            return;
        }

        this.element.value = event.detail.imagePath;

        this.listenForImageSelection = false;
    }

    disconnect() {
        this.element.removeEventListener('click', this.enableImageSelection);
    }
}
