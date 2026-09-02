import { Controller } from '@hotwired/stimulus';
import {visit} from "@hotwired/turbo";

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {
        url: String,
        frameId: String,
        navigateOnLoad: {type: Boolean, default: false}
    };


    connect() {
        this.navigate = this.navigate.bind(this);

        if (this.navigateOnLoadValue) {
            this.navigate();
        }

        this.element.addEventListener('click', this.navigate);
    }

    navigate() {
        visit(this.urlValue, {frame: this.frameIdValue});
    }

    disconnect() {
        this.element.removeEventListener('click', this.navigate);
    }
}
