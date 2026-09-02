import {Controller} from '@hotwired/stimulus';
import Sortable from "sortablejs"
import flash from "../flash";

export default class extends Controller {

    static values = {
        updatePositionUrl: String,
        flashUrl: String,
    }

    connect() {
        this.sortable = Sortable.create(this.element, {
            ghostClass: 'blue-background-during-drag',
            animation: 300,
            onEnd: (event) => {
                const currentItemId = event.item.dataset.id;
                fetch(this.updatePositionUrlValue, {
                    method: 'POST',
                    body: new URLSearchParams({
                        currentItemId: currentItemId,
                        newIndex: event.newIndex,
                        oldIndex: event.oldIndex
                    })
                    //on success
                }).then((response) => {
                    if (response.ok) {
                        //get response message
                        response.json().then((data) => {
                            if (data.message && this.flashUrlValue) {
                                flash(data.message, 'success', this.flashUrlValue);
                            }
                        });
                    } else {
                        //get response message
                        response.json().then((data) => {
                            if (data.message && this.flashUrlValue) {
                                flash(data.message, 'error', this.flashUrlValue);
                            }
                        });
                    }
                });
            }
        });
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = null;
    }
}
