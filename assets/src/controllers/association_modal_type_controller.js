import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        class: String
    }

    static targets = ['selectItem'];

    connect() {
        this.updateAutocomplete = this.updateAutocomplete.bind(this);
        window.addEventListener('add-autocomplete-item:update', this.updateAutocomplete);
    }

    updateAutocomplete(event) {

        if (event.detail.class !== this.classValue) {
            return;
        }

        this.tomSelectControl = this.element.querySelector('select').tomselect;

        this.tomSelectControl.addOption({
            value: event.detail.itemId,
            text: event.detail.itemName
        });

        this.tomSelectControl.addItem(event.detail.itemId);
    }

    disconnect() {
        window.removeEventListener('add-autocomplete-item:update', this.updateAutocomplete);
    }
}
