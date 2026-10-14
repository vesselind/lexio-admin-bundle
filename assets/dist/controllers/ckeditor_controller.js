var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/ckeditor_controller.js
import { Controller } from "@hotwired/stimulus";
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
  MediaEmbed as MediaEmbed2,
  MediaEmbedToolbar,
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
} from "ckeditor5";
import "ckeditor5/ckeditor5.css";

// assets/src/CKEditor/InsertImage.js
import { ButtonView } from "ckeditor5";

// assets/src/CKEditor/CustomUploadAdapter.js
var CustomUploadAdapter = class {
  constructor(loader, uploadUrl) {
    this.loader = loader;
    this.xhr = null;
    this.uploadUrl = uploadUrl;
  }
  // Starts the upload process.
  upload() {
    let url = this.uploadUrl, self = this;
    return this.loader.file.then((uploadedFile) => {
      return new Promise((resolve, reject) => {
        const data = new FormData();
        data.append("upload", uploadedFile);
        self.xhr = fetch(url, {
          method: "POST",
          body: data
        }).then((response) => response.json()).then((data2) => {
          resolve({
            default: data2.url
          });
        }).catch((error) => {
          reject(error && error.detail ? error.detail : "Upload failed.");
        });
        ;
      });
    });
  }
  // Aborts the upload process.
  abort() {
    if (this.xhr) {
      this.xhr.abort();
    }
  }
};

// assets/src/CKEditor/MediaEmbedResize.js
import {
  Collection,
  Command,
  MediaEmbed,
  Plugin,
  ViewModel,
  addListToDropdown,
  createDropdown
} from "ckeditor5";
var RESIZE_ICON = '<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M3.5 10 7 6.5v2h6v-2l3.5 3.5L13 13.5v-2H7v2L3.5 10z"/></svg>';
var WIDTH_PATTERN = /^\d+(\.\d+)?(px|%|em|rem)$/;
function getSelectedMedia(editor) {
  const selected = editor.model.document.selection.getSelectedElement();
  return selected && selected.is("element", "media") ? selected : null;
}
function getFigureAttributes(media) {
  return media.getAttribute("htmlFigureAttributes") ?? {};
}
function getFigureWidth(media) {
  return getFigureAttributes(media).styles?.width ?? null;
}
function withWidth(attributes, width) {
  const updated = { ...attributes };
  const styles = { ...updated.styles ?? {} };
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
  if (typeof value !== "string") {
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
    if (child.is("element", "figure")) {
      return child;
    }
  }
  return null;
}
var ResizeMediaEmbedCommand = class extends Command {
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
    editor.model.change((writer) => {
      if (Object.keys(attributes).length > 0) {
        writer.setAttribute("htmlFigureAttributes", attributes, media);
      } else {
        writer.removeAttribute("htmlFigureAttributes", media);
      }
    });
  }
};
var MediaEmbedResizeUI = class extends Plugin {
  init() {
    const editor = this.editor;
    editor.ui.componentFactory.add("resizeMediaEmbed", (locale) => {
      const t = locale.t;
      const command = editor.commands.get("resizeMediaEmbed");
      const dropdownView = createDropdown(locale);
      const items = new Collection();
      for (const [label, commandParam] of [
        [t("media_resize.original"), null],
        [t("media_resize.preset_75"), "75%"],
        [t("media_resize.preset_50"), "50%"],
        [t("media_resize.preset_25"), "25%"],
        [t("media_resize.custom"), "custom"]
      ]) {
        items.add({
          type: "button",
          model: new ViewModel({ label, withText: true, commandParam })
        });
      }
      dropdownView.buttonView.set({
        label: t("media_resize.label"),
        icon: RESIZE_ICON,
        tooltip: true
      });
      addListToDropdown(dropdownView, items);
      dropdownView.bind("isEnabled").to(command);
      dropdownView.buttonView.bind("label").to(command, "value", (value) => value ? t("media_resize.current_width", value) : t("media_resize.label"));
      dropdownView.on("execute", (evt) => {
        const commandParam = evt.source.commandParam;
        if (commandParam === "custom") {
          const customWidth = normalizeWidth(prompt(t("media_resize.width_prompt")));
          if (customWidth !== null) {
            editor.execute("resizeMediaEmbed", { width: customWidth });
          }
          return;
        }
        editor.execute("resizeMediaEmbed", { width: commandParam });
      });
      return dropdownView;
    });
  }
};
var MediaEmbedResize = class extends Plugin {
  static get requires() {
    return [MediaEmbed, MediaEmbedResizeUI];
  }
  static get pluginName() {
    return "MediaEmbedResize";
  }
  init() {
    const editor = this.editor;
    editor.commands.add("resizeMediaEmbed", new ResizeMediaEmbedCommand(editor));
    editor.conversion.for("editingDowncast").add((dispatcher) => {
      dispatcher.on("attribute:htmlFigureAttributes:media", (evt, data, conversionApi) => {
        if (!conversionApi.consumable.consume(data.item, evt.name)) {
          return;
        }
        const viewElement = conversionApi.mapper.toViewElement(data.item);
        if (!viewElement) {
          return;
        }
        const figure = viewElement.is("element", "figure") ? viewElement : findFigure(viewElement);
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
};

// assets/src/controllers/ckeditor_controller.js
var ckeditor_controller_default = class extends Controller {
  initialize() {
    this.uploadUrl = this.uploadUrlValue || this.element.getAttribute("data-upload-url");
  }
  connect() {
    this.editorPromise = ClassicEditor.create(this.element, {
      language: this.localeValue,
      translations: [this.translationsValue],
      toolbar: {
        items: [
          "undo",
          "redo",
          "|",
          "sourceEditing",
          "showBlocks",
          "findAndReplace",
          "|",
          "heading",
          "|",
          "bold",
          "italic",
          "underline",
          "|",
          "fontColor",
          "fontBackgroundColor",
          "|",
          "link",
          "insertImage",
          "insertTable",
          "blockQuote",
          "htmlEmbed",
          "mediaEmbed",
          "|",
          "bulletedList",
          "numberedList",
          "todoList",
          "outdent",
          "indent"
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
        MediaEmbed2,
        MediaEmbedToolbar,
        MediaEmbedResize,
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
      balloonToolbar: ["bold", "italic", "|", "link", "insertImage", "|", "bulletedList", "numberedList"],
      heading: {
        options: [
          {
            model: "paragraph",
            title: "Paragraph",
            class: "ck-heading_paragraph"
          },
          // {
          //     model: 'heading1',
          //     view: 'h1',
          //     title: 'Heading 1',
          //     class: 'ck-heading_heading1'
          // },
          {
            model: "heading2",
            view: "h2",
            title: "Heading 2",
            class: "ck-heading_heading2"
          },
          {
            model: "heading3",
            view: "h3",
            title: "Heading 3",
            class: "ck-heading_heading3"
          },
          {
            model: "heading4",
            view: "h4",
            title: "Heading 4",
            class: "ck-heading_heading4"
          },
          {
            model: "heading5",
            view: "h5",
            title: "Heading 5",
            class: "ck-heading_heading5"
          },
          {
            model: "heading6",
            view: "h6",
            title: "Heading 6",
            class: "ck-heading_heading6"
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
          "toggleImageCaption",
          "imageTextAlternative",
          "|",
          "imageStyle:inline",
          "imageStyle:wrapText",
          "imageStyle:breakText",
          "|",
          "resizeImage"
        ]
      },
      link: {
        addTargetToExternalLinks: true,
        defaultProtocol: "https://",
        decorators: {
          toggleDownloadable: {
            mode: "manual",
            label: "Downloadable",
            attributes: {
              download: "file"
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
      mediaEmbed: {
        toolbar: ["resizeMediaEmbed"]
      },
      placeholder: "",
      table: {
        contentToolbar: ["tableColumn", "tableRow", "mergeTableCells", "tableProperties", "tableCellProperties"]
      }
    });
  }
  disconnect() {
    this.editorPromise?.then((editor) => editor.destroy());
  }
};
__publicField(ckeditor_controller_default, "values", {
  uploadUrl: String,
  locale: { type: String, default: "en" },
  translations: Object
});
function myCustomUploadAdapterPlugin(editor) {
  let uploadUrl = editor.config.get("customUploadUrl");
  editor.plugins.get("FileRepository").createUploadAdapter = (loader) => {
    return new CustomUploadAdapter(loader, uploadUrl);
  };
}
export {
  ckeditor_controller_default as default
};
