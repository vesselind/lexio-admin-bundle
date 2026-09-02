import {Controller} from '@hotwired/stimulus';
import * as Bootstrap from 'bootstrap';
import {visit} from "@hotwired/turbo";

export default class extends Controller {

    static targets = ['turboFrame', 'title'];

    connect() {
        this.modal = Bootstrap.Modal.getOrCreateInstance(this.element);
        this.onModalHide = this.onModalHide.bind(this);
        this.openModal = this.openModal.bind(this);
        this.onSubmitEnd = this.onSubmitEnd.bind(this);

        this.element.addEventListener('hide.bs.modal', this.onModalHide);
    }

    openModal(event) {
        this.toggleLoadingIndicator();

        this.modal = Bootstrap.Modal.getOrCreateInstance(this.element);


        if (event.detail.visitUrl) {
            visit(event.detail.visitUrl, {'frame': this.turboFrameTarget.getAttribute('id')});
        }

        this.redirectOnSuccessToUrl = event.detail.redirectOnSuccessToUrl;
        this.redirectOnSuccessToFrame = event.detail.redirectOnSuccessToFrame ?? 'base-modal-body';
        this.closeOnSuccess = event.detail.closeOnSuccess;

        this.titleTarget.textContent = event.detail.modalTitle;
        this.element.querySelector('.modal-dialog').classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        this.element.querySelector('.modal-dialog').classList.add(event.detail.modalSize ?? 'modal-md');

        this.modal.show();

        this.turboFrameTarget.removeEventListener('turbo:submit-end', this.onSubmitEnd);
        this.turboFrameTarget.addEventListener('turbo:submit-end', this.onSubmitEnd);
    }

    onSubmitEnd(event) {
        if (!event.detail.success) {
            return;
        }

        if (this.redirectOnSuccessToUrl) {
            visit(this.redirectOnSuccessToUrl, {'frame': this.redirectOnSuccessToFrame});
        }

        this.onModalSuccess();
    }

    toggleLoadingIndicator() {
        const template = this.element.querySelector('template');

        const turboFrame = this.element.querySelector('turbo-frame');

        if (turboFrame && template?.innerHTML) {
            this.element.querySelector('turbo-frame').innerHTML = template.innerHTML;
        }
    }


    onModalSuccess() {
        if (this.closeOnSuccess) {
            this.closeModal();
        }
    }

    closeModal() {
        this.modal.hide();

        this.onModalHide();

        document.body.classList.remove("modal-show");
    }

    onModalHide() {
        let turboElement = this.element.querySelector('turbo-frame');

        if (turboElement) {
            turboElement.innerHTML = '';
        }
    }

    disconnect() {
        document.body.style.overflow = 'auto';
        super.disconnect();

        this.modal.dispose();
        this.turboFrameTarget?.removeEventListener('turbo:submit-end', this.onSubmitEnd);
        this.element.removeEventListener('hide.bs.modal', this.onModalHide);

        this.element.style.display = 'none';

        this.onModalHide();
    }
}
