var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/file-upload-form_controller.js
import { Controller } from "@hotwired/stimulus";
var file_upload_form_controller_default = class extends Controller {
  selectFile() {
    this.inputTarget.click();
  }
  submitForm() {
    this.iconTarget.classList.toggle("fa-upload");
    this.spinnerTarget.classList.remove("d-none");
    if (this.hasSubmitButtonTarget) {
      this.submitButtonTarget.click();
    } else {
      this.element.submit();
    }
  }
};
__publicField(file_upload_form_controller_default, "values", {
  uploadUrl: String,
  redirectUrl: String
});
__publicField(file_upload_form_controller_default, "targets", ["input", "icon", "spinner", "submitButton"]);
export {
  file_upload_form_controller_default as default
};
