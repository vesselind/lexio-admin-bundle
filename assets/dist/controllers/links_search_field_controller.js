var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/links_search_field_controller.js
import { Controller } from "@hotwired/stimulus";
var links_search_field_controller_default = class extends Controller {
  selectLink(e) {
    e.preventDefault();
    e.stopPropagation();
    const href = e.params.href;
    const title = e.params.title;
    let ckEditable = this.element.parentElement.querySelector('[contenteditable="true"]');
    this.editor = ckEditable.ckeditorInstance;
    const linkTemplate = `<a href="${href}">${title}</a>`;
    const viewFragment = this.editor.data.processor.toView(linkTemplate);
    const modelFragment = this.editor.data.toModel(viewFragment);
    this.editor.model.change((writer) => {
      this.editor.model.insertContent(modelFragment, this.editor.model.document.selection);
    });
    let badgeAdded = document.createElement("span");
    badgeAdded.classList.add("badge", "bg-success", "ms-1");
    badgeAdded.innerHTML = '<i class="fa fa-check"></i> ' + this.addedBadgeTextValue;
    e.currentTarget.append(badgeAdded);
  }
};
__publicField(links_search_field_controller_default, "values", {
  addedBadgeText: { type: String }
});
export {
  links_search_field_controller_default as default
};
