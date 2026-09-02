import { ButtonView } from 'ckeditor5';
import InsertImageViaURL from "./InsertImageViaURL";

export default class InsertImage {
    constructor(editor) {
        this.editor = editor;
    }

    init() {
        const editor = this.editor;

        this.editor.commands.add('insertImageViaURL', new InsertImageViaURL(this.editor));

        editor.ui.componentFactory.add('insertImage', locale => {
            const view = new ButtonView(locale);

            view.set({
                label: 'Insert image',
                tooltip: true,
                withText: true,
            });

            // Callback executed once the image is clicked.
            view.on('execute', () => {
                const imageUrl = prompt('Image URL');

                editor.model.change(writer => {
                    const imageElement = writer.createElement('image', {
                        src: imageUrl
                    });

                    // Insert the image in the current selection location.
                    editor.model.insertContent(imageElement, editor.model.document.selection);
                });
            });

            return view;
        });
    }
}
