import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['addedBadgeTemplate'];

    static values = {
        addedBadgeText: {type: String},
    }

    selectLink(e) {
        e.preventDefault();
        e.stopPropagation();

        const href = e.params.href;
        const title = e.params.title;

        let ckEditable = this.element.parentElement.querySelector('[contenteditable="true"]');

        this.editor = ckEditable.ckeditorInstance;

        // Assuming `editor` is your CKEditor 5 instance.
        const linkTemplate = `<a href="${href}">${title}</a>`;

        // Convert HTML to a document fragment that CKEditor can understand
        const viewFragment = this.editor.data.processor.toView(linkTemplate);

        // Convert the view fragment to the model document fragment
        const modelFragment = this.editor.data.toModel(viewFragment);

        // Insert the content at the current selection
        this.editor.model.change(writer => {
            this.editor.model.insertContent(modelFragment, this.editor.model.document.selection);
        });

        const badgeAdded = this.addedBadgeTemplateTarget.content.cloneNode(true);
        badgeAdded.querySelector('[data-added-badge-text]').textContent = this.addedBadgeTextValue;
        e.currentTarget.append(badgeAdded)
    }
}
