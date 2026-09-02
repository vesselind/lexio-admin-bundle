import { Controller } from "@hotwired/stimulus"

const FLASH_DURATION = 5000;

export default class extends Controller {

    static targets = ['timer'];

    connect() {
        this.timerFrame = requestAnimationFrame(() => {
            if (this.hasTimerTarget) {
                this.timerTarget.style.width = '0%';
            }
        });

        this.timeout = setTimeout(() => {
            this.element.classList.add('d-none');
        }, FLASH_DURATION);
    }

    close()
    {
        this.element.classList.add('d-none');
    }

    disconnect() {
        clearTimeout(this.timeout);
        cancelAnimationFrame(this.timerFrame);
    }
}
