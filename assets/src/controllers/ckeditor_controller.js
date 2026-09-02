import {Controller} from "@hotwired/stimulus";

import {
    ClassicEditor,
    AccessibilityHelp,
    Autoformat,
    AutoImage,
    Autosave,
    BalloonToolbar,
    BlockQuote,
    Bold,
    Essentials,
    FindAndReplace,
    FullPage,
    GeneralHtmlSupport,
    Heading,
    HtmlComment,
    HtmlEmbed,
    ImageBlock,
    ImageCaption,
    ImageInline,
    ImageInsert,
    ImageInsertViaUrl,
    ImageResize,
    ImageStyle,
    ImageTextAlternative,
    ImageToolbar,
    ImageUpload,
    Indent,
    IndentBlock,
    Italic,
    Link,
    LinkImage,
    List,
    ListProperties,
    Paragraph,
    PasteFromMarkdownExperimental,
    PasteFromOffice,
    SelectAll,
    ShowBlocks,
    SimpleUploadAdapter,
    SourceEditing,
    Table,
    TableCaption,
    TableCellProperties,
    TableColumnResize,
    TableProperties,
    TableToolbar,
    TextTransformation,
    TodoList,
    Underline,
    Undo,
    Font
} from 'ckeditor5';

import 'ckeditor5/ckeditor5.css';
import InsertImage from '../CKEditor/InsertImage';
import CustomUploadAdapter from '../CKEditor/CustomUploadAdapter';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        uploadUrl: String,
    };

    initialize() {
        // `data-upload-url` is retained as a compatibility alias for hosts
        // that have not yet adopted the Stimulus value attribute.
        this.uploadUrl = this.uploadUrlValue || this.element.getAttribute('data-upload-url');

    }

    connect() {

       this.editorPromise = ClassicEditor.create(this.element, {
            toolbar: {
                items: [
                    'undo',
                    'redo',
                    '|',
                    'sourceEditing',
                    'showBlocks',
                    'findAndReplace',
                    '|',
                    'heading',
                    '|',
                    'bold',
                    'italic',
                    'underline',
                    '|',
                    'fontColor',
                    'fontBackgroundColor',
                    '|',
                    'link',
                    'insertImage',
                    'insertTable',
                    'blockQuote',
                    'htmlEmbed',
                    '|',
                    'bulletedList',
                    'numberedList',
                    'todoList',
                    'outdent',
                    'indent'
                ],
                shouldNotGroupWhenFull: false
            },
            plugins: [
                AccessibilityHelp,
                Autoformat,
                AutoImage,
                Autosave,
                BalloonToolbar,
                BlockQuote,
                Bold,
                Essentials,
                FindAndReplace,
                FullPage,
                GeneralHtmlSupport,
                Heading,
                HtmlComment,
                HtmlEmbed,
                ImageBlock,
                ImageCaption,
                ImageInline,
                ImageInsert,
                ImageInsertViaUrl,
                ImageResize,
                ImageStyle,
                ImageTextAlternative,
                ImageToolbar,
                ImageUpload,
                Indent,
                IndentBlock,
                Italic,
                Link,
                LinkImage,
                List,
                ListProperties,
                Paragraph,
                PasteFromMarkdownExperimental,
                PasteFromOffice,
                SelectAll,
                ShowBlocks,
                SimpleUploadAdapter,
                SourceEditing,
                Table,
                TableCaption,
                TableCellProperties,
                TableColumnResize,
                TableProperties,
                TableToolbar,
                TextTransformation,
                TodoList,
                Underline,
                Undo,
                Font
            ],
            extraPlugins: [myCustomUploadAdapterPlugin],
            customUploadUrl: this.uploadUrl,
            balloonToolbar: ['bold', 'italic', '|', 'link', 'insertImage', '|', 'bulletedList', 'numberedList'],
            heading: {
                options: [
                    {
                        model: 'paragraph',
                        title: 'Paragraph',
                        class: 'ck-heading_paragraph'
                    },
                    // {
                    //     model: 'heading1',
                    //     view: 'h1',
                    //     title: 'Heading 1',
                    //     class: 'ck-heading_heading1'
                    // },
                    {
                        model: 'heading2',
                        view: 'h2',
                        title: 'Heading 2',
                        class: 'ck-heading_heading2'
                    },
                    {
                        model: 'heading3',
                        view: 'h3',
                        title: 'Heading 3',
                        class: 'ck-heading_heading3'
                    },
                    {
                        model: 'heading4',
                        view: 'h4',
                        title: 'Heading 4',
                        class: 'ck-heading_heading4'
                    },
                    {
                        model: 'heading5',
                        view: 'h5',
                        title: 'Heading 5',
                        class: 'ck-heading_heading5'
                    },
                    {
                        model: 'heading6',
                        view: 'h6',
                        title: 'Heading 6',
                        class: 'ck-heading_heading6'
                    }
                ]
            },
            htmlSupport: {
                allow: [
                    {
                        name: /^.*$/,
                        styles: true,
                        attributes: true,
                        classes: true
                    }
                ]
            },
            image: {
                toolbar: [
                    'toggleImageCaption',
                    'imageTextAlternative',
                    '|',
                    'imageStyle:inline',
                    'imageStyle:wrapText',
                    'imageStyle:breakText',
                    '|',
                    'resizeImage',
                ]
            },
            link: {
                addTargetToExternalLinks: true,
                defaultProtocol: 'https://',
                decorators: {
                    toggleDownloadable: {
                        mode: 'manual',
                        label: 'Downloadable',
                        attributes: {
                            download: 'file'
                        }
                    }
                }
            },
            list: {
                properties: {
                    styles: true,
                    startIndex: true,
                    reversed: true
                }
            },
            placeholder: '',
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties']
            }
        });

    }

    disconnect() {
        this.editorPromise?.then((editor) => editor.destroy());
    }
}

function myCustomUploadAdapterPlugin(editor) {
    let uploadUrl = editor.config.get('customUploadUrl');

    editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
        return new CustomUploadAdapter(loader, uploadUrl);
    };
}
