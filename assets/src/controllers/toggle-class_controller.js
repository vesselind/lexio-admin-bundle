import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        class: String
    };



    toggle(e) {
        this.element.querySelectorAll(`.${this.classValue}`).forEach((el) => {
            el.classList.remove(this.classValue);
        });

        e.currentTarget.classList.add(this.classValue);
    }
}
