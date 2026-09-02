import { Controller } from '@hotwired/stimulus';
import * as Bootstrap from 'bootstrap';

export default class extends Controller {
    static values = {
        loadingIndicator: {type: Boolean, default: false},
    }


    connect() {
        this.handleModalHideEvent = this.handleModalHideEvent.bind(this);
        this.openModal = this.openModal.bind(this);
        this.closeModal = this.closeModal.bind(this);

        this.element.addEventListener('hide.bs.modal', this.handleModalHideEvent);

        this.modalId = this.element.getAttribute('id');

        this.modal = Bootstrap.Modal.getOrCreateInstance(this.element);

        //Listen for open and close events dispatched from live components
        this.openEventName = `modal:open:${this.modalId}`;
        document.addEventListener(this.openEventName, this.openModal);
        window.addEventListener('modal:close', this.closeModal);

        if (this.loadingIndicatorValue) {
            this.toggleLoadingIndicator = this.toggleLoadingIndicator.bind(this);
            this.element.addEventListener('show.bs.modal', this.toggleLoadingIndicator);
        }
    }

    handleModalHideEvent(event) {
        document.body.classList.remove("modal-show");
    }

    toggleLoadingIndicator() {
        const template = this.element.querySelector('template');
        this.element.querySelector('turbo-frame').innerHTML = template.innerHTML;
    }

    openModal() {
        this.modal.show();
    }

    closeModal() {
        this.modal.hide();
        document.body.classList.remove('modal-show');
        document.body.classList.remove('nav-open');
        document.body.classList.remove('modal-open');
        document.body.style.overflow = 'auto';

        this.element.querySelector('turbo-frame')?.reload();
    }

    disconnect() {
        document.body.style.overflow = 'auto';
        super.disconnect();

        this.modal.dispose();

        this.element.style.display = 'none';
        this.element.removeEventListener('hide.bs.modal', this.handleModalHideEvent);
        document.removeEventListener(this.openEventName, this.openModal);
        window.removeEventListener('modal:close', this.closeModal);

        if (this.loadingIndicatorValue) {
            this.element.removeEventListener('show.bs.modal', this.toggleLoadingIndicator);
        }
    }
}
