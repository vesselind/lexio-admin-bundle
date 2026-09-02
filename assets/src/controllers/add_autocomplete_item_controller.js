import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        itemId: String,
        itemName: String,
        class: String
    }

    connect() {
        this.dispatch('update', { detail: {class: this.classValue, itemId: this.itemIdValue, itemName: this.itemNameValue }});
    }
}
