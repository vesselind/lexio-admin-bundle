import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['checkboxItem'];

    connect() {
        this.checkedItems = [];
    }

    selectItem(event) {
        this.updateItemsCollection();
    }


    selectAll(event) {

        this.checkboxItemTargets.forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });

        this.updateItemsCollection();

    }


    updateItemsCollection() {
        this.checkboxItemTargets.forEach((checkbox) => {
            let isChecked = checkbox.checked;

            const itemId = checkbox.getAttribute('data-item-id');

            if (isChecked) {
                this.checkedItems.push(itemId);
            } else {
                this.checkedItems = this.checkedItems.filter((itemId) => {
                    return itemId !== checkbox.getAttribute('data-item-id');
                });
            }

            let closestTr = checkbox.closest('tr')

            if (closestTr) {
                closestTr.classList.toggle('selected', isChecked);
            }
        });

        this.checkedItems = [...new Set(this.checkedItems)];

        this.dispatch('itemsUpdated', {detail: this.checkedItems});
    }
}
