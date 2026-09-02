import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["source"]

    copy(event) {
        event.preventDefault()

        const text = this.sourceTarget?.innerHTML || this.sourceTarget.value

        navigator.clipboard.writeText(text).then(() => this.copied())
    }

    copied() {

    }
}
