import { Controller } from "@hotwired/stimulus"
import debounce from 'debounce'

export default class extends Controller {
    initialize() {
        this.debouncedSubmit = debounce(this.debouncedSubmit.bind(this), 300)
    }
    submit() {
        this.element.requestSubmit()
    }
    debouncedSubmit() {
        this.submit()
    }


    selectSubmit(e) {
        this.submit();
        e.currentTarget.focus();
    }
}
