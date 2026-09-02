import {Controller} from "@hotwired/stimulus";
export default class extends Controller {
    static values = {
        uploadUrl: String,
        redirectUrl: String
    }

    static targets = ['input', 'icon', 'spinner', 'submitButton'];

    selectFile() {

        this.inputTarget.click();
    }

    submitForm() {

        this.iconTarget.classList.toggle('fa-upload');
        this.spinnerTarget.classList.remove('d-none');

        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.click();
        } else {
            this.element.submit();
        }
    }
}