export default class InsertImageViaURL {
    constructor(editor) {
        this.editor = editor;
    }

    execute() {
        const url = prompt('Image URL');

        this.editor.model.change(writer => {
            const imageElement = writer.createElement('imageBlock', {
                src: url
            });

            // Insert the image in the current selection location.
            this.editor.model.insertContent(imageElement, this.editor.model.document.selection);
        });
    }

    refresh() {
    }
}