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
    }

    toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        const targetSelector = event.currentTarget?.getAttribute('data-bs-target');
        const targetId = targetSelector?.startsWith('#') ? targetSelector.slice(1) : null;

        if (!targetId || !this.bsCollapse?.[targetId]) {
            return;
        }

        if (this.singleOpenValue) {
            Object.entries(this.bsCollapse).forEach(([id, collapse]) => {
                if (id !== targetId) {
                    collapse.hide();
                }
            });
        }

        this.bsCollapse[targetId].toggle();
    }

    disconnect() {
        Object.values(this.bsCollapse ?? {}).forEach((collapse) => collapse.dispose());
        this.bsCollapse = {};
    }
}
