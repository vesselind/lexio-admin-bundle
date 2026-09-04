import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        csrfToken: String,
    }

    static targets = ['button', 'modalBody'];

    connect() {

        this.checkedItemsIds = [];

        window.addEventListener('check-selection:itemsUpdated', this.updateItems.bind(this))
    }

    updateItems(event) {
        this.checkedItemsIds = event.detail;

        for (let button of this.buttonTargets) {
            if (this.checkedItemsIds.length > 0) {
                button.classList.remove('d-none');
            } else {
                button.classList.add('d-none');
            }
        }
    }

    openConfirmationModal(event) {
        this.url = event.params.url;

        this.modalBodyTarget.innerHTML = this.modalBodyTarget.innerHTML.replaceAll('%action%', event.currentTarget.innerText.trim());

        this.modalBodyTarget.innerHTML = this.modalBodyTarget.innerHTML.replaceAll('%count%', this.checkedItemsIds.length);
    }

    confirm(event) {
        event.preventDefault();

        const searchParams = new URLSearchParams();

        this.checkedItemsIds.forEach(id => searchParams.append('ids[]', id));

        const url = `${this.url}?${searchParams.toString()}`;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': this.csrfTokenValue,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then((response) => {
            window.location.href = response.url;
        });
    }
}
