import {Controller} from "@hotwired/stimulus"
import flash from "../flash";
import {visit} from "@hotwired/turbo";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        uploadUrl: String,
        currentUrl: String,
        currentFrame: String,
        flashUrl: String,
    }

    static targets = ['file'];
    selectImage(event) {

        const imageId = event.params.imageId;
        const imagePath = event.params.imagePath;

        this.dispatch('image-selected', {
            detail: {
                imageId: imageId,
                imagePath: imagePath
            }
        });

        window.dispatchEvent(new CustomEvent('base-modal:close'));
    }

    uploadImageSubmit(e){
        e.preventDefault();

        const input = this.hasFileTarget ? this.fileTarget : document.getElementById('file');
        const image = input?.files?.[0];

        if (!image) {
            return;
        }

        const formData = new FormData();

        formData.append("file", image);
        fetch(this.uploadUrlValue, {method: "POST", body: formData})
            .then((response) => response.json().then((data) => ({response, data})))
            .then(({response, data}) => {
                const flashType = response.ok ? 'success' : 'error';
                if (data.message && this.flashUrlValue) {
                    flash(data.message, flashType, this.flashUrlValue);
                }
                visit(this.currentUrlValue, {frame: this.currentFrameValue});
            });
    }

}
