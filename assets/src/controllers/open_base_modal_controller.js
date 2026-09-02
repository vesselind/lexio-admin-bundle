import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        modalSize: {type: String, default: null},
        modalTitle: {type: String, default: null},
        visitUrl: {type: String, default: null},
        redirectOnSuccessToUrl: {type: String, default: null},
        redirectOnSuccessToFrame: {type: String, default: null},
        closeOnSuccess: {type: Boolean, default: true}
    }


    connect() {
        this.openModal = () => {
            window.dispatchEvent(new CustomEvent('base-modal:open', {
                detail: {
                    modalSize: this.modalSizeValue,
                    modalTitle: this.modalTitleValue,
                    visitUrl: this.visitUrlValue,
                    redirectOnSuccessToUrl: this.redirectOnSuccessToUrlValue,
                    redirectOnSuccessToFrame: this.redirectOnSuccessToFrameValue,
                    closeOnSuccess: this.closeOnSuccessValue
                }
            }));
        };

        this.element.addEventListener('click', this.openModal);
    }

    disconnect() {
        this.element.removeEventListener('click', this.openModal);
    }
}
