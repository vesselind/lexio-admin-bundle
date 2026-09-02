import { Controller } from '@hotwired/stimulus';
import * as Bootstrap from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        title: String,
        placement: {type: String, default: 'bottom'},
    }

    connect(){
        this.hideTooltip = this.hideTooltip.bind(this);
        this.tooltip = Bootstrap.Tooltip.getOrCreateInstance(this.element, {title: this.titleValue, placement: this.placementValue});

        //force hiding tooltip when mouse is out of the element
        this.element.addEventListener('mouseleave', this.hideTooltip);
        this.element.addEventListener('click', this.hideTooltip);
    }

    hideTooltip(){
        this.tooltip.hide();
    }

    disconnect() {
        this.element.removeEventListener('mouseleave', this.hideTooltip);
        this.element.removeEventListener('click', this.hideTooltip);
        this.tooltip?.dispose();
    }

}
