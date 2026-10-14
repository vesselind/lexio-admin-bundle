import {
    Collection,
    Command,
    MediaEmbed,
    Plugin,
    ViewModel,
    addListToDropdown,
    createDropdown
} from 'ckeditor5';

const RESIZE_ICON = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">'
    + '<path d="M3.5 10 7 6.5v2h6v-2l3.5 3.5L13 13.5v-2H7v2L3.5 10z"/>'
    + '</svg>';

const WIDTH_PATTERN = /^\d+(\.\d+)?(px|%|em|rem)$/;

function getSelectedMedia(editor) {
    const selected = editor.model.document.selection.getSelectedElement();

    return selected && selected.is('element', 'media') ? selected : null;
}

function getFigureAttributes(media) {
    return media.getAttribute('htmlFigureAttributes') ?? {};
}

function getFigureWidth(media) {
    return getFigureAttributes(media).styles?.width ?? null;
}

function withWidth(attributes, width) {
    const updated = { ...attributes };
    const styles = { ...(updated.styles ?? {}) };

    if (width) {
        styles.width = width;
    } else {
        delete styles.width;
    }

    if (Object.keys(styles).length > 0) {
        updated.styles = styles;
    } else {
        delete updated.styles;
    }

    return updated;
}

function normalizeWidth(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const trimmed = value.trim();

    if (/^\d+(\.\d+)?$/.test(trimmed)) {
        return `${trimmed}px`;
    }

    return WIDTH_PATTERN.test(trimmed) ? trimmed : null;
}

function findFigure(viewElement) {
    for (const child of viewElement.getChildren()) {
        if (child.is('element', 'figure')) {
            return child;
        }
    }

    return null;
}

class ResizeMediaEmbedCommand extends Command {
    refresh() {
        const media = getSelectedMedia(this.editor);

        this.isEnabled = Boolean(media);
        this.value = media ? getFigureWidth(media) : null;
    }

    execute(options = {}) {
        const editor = this.editor;
        const media = getSelectedMedia(editor);

        if (!media) {
            return;
        }

        const attributes = withWidth(getFigureAttributes(media), options.width ?? null);

        editor.model.change(writer => {
            if (Object.keys(attributes).length > 0) {
                writer.setAttribute('htmlFigureAttributes', attributes, media);
            } else {
                writer.removeAttribute('htmlFigureAttributes', media);
            }
        });
    }
}

class MediaEmbedResizeUI extends Plugin {
    init() {
        const editor = this.editor;

        editor.ui.componentFactory.add('resizeMediaEmbed', locale => {
            const t = locale.t;
            const command = editor.commands.get('resizeMediaEmbed');
            const dropdownView = createDropdown(locale);
            const items = new Collection();

            for (const [label, commandParam] of [
                [t('media_resize.original'), null],
                [t('media_resize.preset_75'), '75%'],
                [t('media_resize.preset_50'), '50%'],
                [t('media_resize.preset_25'), '25%'],
                [t('media_resize.custom'), 'custom']
            ]) {
                items.add({
                    type: 'button',
                    model: new ViewModel({ label, withText: true, commandParam })
                });
            }

            dropdownView.buttonView.set({
                label: t('media_resize.label'),
                icon: RESIZE_ICON,
                tooltip: true
            });

            addListToDropdown(dropdownView, items);

            dropdownView.bind('isEnabled').to(command);
            dropdownView.buttonView.bind('label').to(command, 'value', value => (
                value ? t('media_resize.current_width', value) : t('media_resize.label')
            ));

            dropdownView.on('execute', evt => {
                const commandParam = evt.source.commandParam;

                if (commandParam === 'custom') {
                    const customWidth = normalizeWidth(prompt(t('media_resize.width_prompt')));

                    if (customWidth !== null) {
                        editor.execute('resizeMediaEmbed', { width: customWidth });
                    }

                    return;
                }

                editor.execute('resizeMediaEmbed', { width: commandParam });
            });

            return dropdownView;
        });
    }
}

export default class MediaEmbedResize extends Plugin {
    static get requires() {
        return [MediaEmbed, MediaEmbedResizeUI];
    }

    static get pluginName() {
        return 'MediaEmbedResize';
    }

    init() {
        const editor = this.editor;

        editor.commands.add('resizeMediaEmbed', new ResizeMediaEmbedCommand(editor));

        editor.conversion.for('editingDowncast').add(dispatcher => {
            dispatcher.on('attribute:htmlFigureAttributes:media', (evt, data, conversionApi) => {
                if (!conversionApi.consumable.consume(data.item, evt.name)) {
                    return;
                }

                const viewElement = conversionApi.mapper.toViewElement(data.item);

                if (!viewElement) {
                    return;
                }

                const figure = viewElement.is('element', 'figure') ? viewElement : findFigure(viewElement);

                if (!figure) {
                    return;
                }

                for (const property of Object.keys(data.attributeOldValue?.styles ?? {})) {
                    conversionApi.writer.removeStyle(property, figure);
                }

                for (const [property, value] of Object.entries(data.attributeNewValue?.styles ?? {})) {
                    conversionApi.writer.setStyle(property, value, figure);
                }
            });
        });
    }
}
