var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/turnstile_controller.js
import { Controller } from "@hotwired/stimulus";
var turnstile_controller_default = class extends Controller {
  connect() {
    if (window.turnstile) {
      this.renderTurnstile();
    } else {
      this.loadTurnstileScript();
    }
  }
  loadTurnstileScript() {
    const existingScript = document.querySelector(`script[src="${this.scriptUrlValue}"]`);
    if (existingScript) {
      existingScript.addEventListener("load", () => this.renderTurnstile(), { once: true });
      return;
    }
    const script = document.createElement("script");
    script.src = this.scriptUrlValue;
    script.async = true;
    script.defer = true;
    script.onload = () => this.renderTurnstile();
    document.head.appendChild(script);
  }
  renderTurnstile() {
    if (!window.turnstile) {
      return;
    }
    window.turnstile.render(this.element, {
      sitekey: this.apiKeyValue || this.element.getAttribute("data-api-key"),
      callback: (token) => this.onSuccess(token)
    });
  }
  onSuccess(token) {
    if (this.element) {
      this.element.value = token;
    }
  }
};
__publicField(turnstile_controller_default, "values", {
  apiKey: String,
  scriptUrl: {
    type: String,
    default: "https://challenges.cloudflare.com/turnstile/v0/api.js"
  }
});
export {
  turnstile_controller_default as default
};
