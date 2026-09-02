import { Controller } from '@hotwired/stimulus';
import * as Bootstrap from 'bootstrap';

export default class extends Controller {
    static values = {
        singleOpen: {type: Boolean, default: false},
    };

    connect() {
        const collapseElements = Array.from(this.element.querySelectorAll('.dropdown-panel > .collapse'));

        this.bsCollapse = Object.fromEntries(collapseElements.map((element) => [
            element.id,
            new Bootstrap.Collapse(element, {toggle: false}),
        ]));

        if (this.singleOpenValue) {
            this.closeOtherPanels = () => {
                collapseElements.forEach((element) => this.bsCollapse[element.id]?.hide());
            };

            this.element.querySelectorAll('.btn-toggle').forEach((item) => {
                item.addEventListener('click', this.closeOtherPanels);
            });
        }
    }

    disconnect() {
        if (this.closeOtherPanels) {
            this.element.querySelectorAll('.btn-toggle').forEach((item) => {
                item.removeEventListener('click', this.closeOtherPanels);
            });
        }

        Object.values(this.bsCollapse ?? {}).forEach((collapse) => collapse.dispose());
        this.bsCollapse = {};
    }
}
