import { Controller } from '@hotwired/stimulus';
import flash from "../flash";
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        successMessage: {type: String, default: null},
        flashUrl: {type: String, default: null}
    }
    static targets = ['textarea']

    insertText(event) {
        const currentText = this.textareaTarget.value;

        //get cursor position
        const cursorPosition = this.textareaTarget.selectionStart;

        //insert text at cursor position
        this.textareaTarget.value = currentText.slice(0, cursorPosition) + '{{ ' + event.target.textContent.trim() + ' }}' + currentText.slice(cursorPosition);

        if (this.successMessageValue && this.flashUrlValue) {
            flash(this.successMessageValue, 'success', this.flashUrlValue);
        }
    }
}
