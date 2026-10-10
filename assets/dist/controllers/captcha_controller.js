var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/captcha_controller.js
import { Controller } from "@hotwired/stimulus";
var scriptLoads = /* @__PURE__ */ new WeakMap();
var timeout = 15e3;
var captcha_controller_default = class extends Controller {
  connect() {
    this.element.value = "";
    this.form = this.element.closest("form");
    this.connection = /* @__PURE__ */ Symbol();
    this.isRefreshing = false;
    this.isResubmitting = false;
    this.hasSubmitted = false;
    this.abortController = new AbortController();
    this.onSubmit = this.refreshTokenAndSubmit.bind(this);
    this.onPageShow = (event) => {
      if (event.persisted) {
        this.connection = /* @__PURE__ */ Symbol();
        this.abortController.abort();
        this.abortController = new AbortController();
        this.element.value = "";
        this.isRefreshing = false;
        this.isResubmitting = false;
        this.hasSubmitted = false;
        this.clearError();
      }
    };
    if (!this.form) {
      return;
    }
    this.form.addEventListener("submit", this.onSubmit, true);
    this.element.ownerDocument.defaultView.addEventListener("pageshow", this.onPageShow);
    if (this.siteKeyValue) {
      this.loadRecaptcha().catch(() => {
      });
    }
  }
  disconnect() {
    this.connection = null;
    this.abortController?.abort();
    this.form?.removeEventListener("submit", this.onSubmit, true);
    this.element.ownerDocument.defaultView.removeEventListener("pageshow", this.onPageShow);
    this.clearError();
    this.element.value = "";
  }
  async refreshTokenAndSubmit(event) {
    if (this.isResubmitting) {
      this.resubmitEvent = event;
      return;
    }
    event.preventDefault();
    event.stopImmediatePropagation();
    if (this.isRefreshing || this.hasSubmitted) {
      return;
    }
    const connection = this.connection;
    this.isRefreshing = true;
    this.element.value = "";
    this.clearError();
    try {
      const token = await this.execute();
      if (connection !== this.connection || !this.element.isConnected || !this.form?.isConnected) {
        return;
      }
      if (typeof token !== "string" || token.trim() === "") {
        throw new Error("Google reCAPTCHA returned an empty token.");
      }
      this.element.value = token;
      this.isResubmitting = true;
      this.resubmitEvent = null;
      if (event.submitter?.form === this.form) {
        this.form.requestSubmit(event.submitter);
      } else {
        this.form.requestSubmit();
      }
      this.hasSubmitted = this.resubmitEvent !== null && !this.resubmitEvent.defaultPrevented;
      if (!this.hasSubmitted) {
        this.element.value = "";
      }
    } catch {
      if (connection === this.connection && this.element.isConnected) {
        this.element.value = "";
        this.showError();
      }
    } finally {
      if (connection === this.connection) {
        this.isResubmitting = false;
        this.isRefreshing = false;
      }
    }
  }
  loadRecaptcha() {
    const document = this.element.ownerDocument;
    const api = document.defaultView.grecaptcha;
    if (typeof api?.ready === "function" && typeof api?.execute === "function") {
      return Promise.resolve(api);
    }
    if (scriptLoads.has(document)) {
      return scriptLoads.get(document);
    }
    if (!this.siteKeyValue) {
      return Promise.reject(new Error("Google reCAPTCHA site key is missing."));
    }
    const promise = new Promise((resolve, reject) => {
      let script = document.querySelector("script[data-google-recaptcha-v3]");
      const isNew = !script;
      if (isNew) {
        script = document.createElement("script");
        const url = new URL(this.scriptUrlValue, document.baseURI);
        url.searchParams.set("render", this.siteKeyValue);
        script.src = url.toString();
        script.async = true;
        script.defer = true;
        script.dataset.googleRecaptchaV3 = "loading";
      }
      const cleanup = () => {
        clearTimeout(timer);
        script.removeEventListener("load", onLoad);
        script.removeEventListener("error", onError);
      };
      const onError = () => {
        cleanup();
        script.remove();
        reject(new Error("Google reCAPTCHA could not be loaded."));
      };
      const onLoad = () => {
        const api2 = document.defaultView.grecaptcha;
        if (typeof api2?.ready !== "function" || typeof api2?.execute !== "function") {
          onError();
          return;
        }
        cleanup();
        script.dataset.googleRecaptchaV3 = "ready";
        resolve(api2);
      };
      const timer = setTimeout(onError, timeout);
      script.addEventListener("load", onLoad, { once: true });
      script.addEventListener("error", onError, { once: true });
      if (script.dataset.googleRecaptchaV3 === "ready") {
        onLoad();
      } else if (isNew) {
        document.head.appendChild(script);
      }
    });
    scriptLoads.set(document, promise);
    promise.catch(() => {
      if (scriptLoads.get(document) === promise) {
        scriptLoads.delete(document);
      }
    });
    return promise;
  }
  async execute() {
    const signal = this.abortController.signal;
    const api = await this.loadRecaptcha();
    return new Promise((resolve, reject) => {
      const cleanup = () => {
        clearTimeout(timer);
        signal.removeEventListener("abort", onAbort);
      };
      const finish = (callback, value) => {
        cleanup();
        callback(value);
      };
      const onAbort = () => finish(reject, new Error("Google reCAPTCHA request was cancelled."));
      const timer = setTimeout(() => finish(reject, new Error("Google reCAPTCHA timed out.")), timeout);
      signal.addEventListener("abort", onAbort, { once: true });
      if (signal.aborted) {
        onAbort();
        return;
      }
      try {
        api.ready(() => {
          if (signal.aborted) {
            return;
          }
          try {
            Promise.resolve(api.execute(this.siteKeyValue, { action: this.actionValue })).then((token) => finish(resolve, token), (error) => finish(reject, error));
          } catch (error) {
            finish(reject, error);
          }
        });
      } catch (error) {
        finish(reject, error);
      }
    });
  }
  showError() {
    this.errorElement = this.element.ownerDocument.createElement("p");
    this.errorElement.setAttribute("role", "alert");
    this.errorElement.className = "alert alert-danger";
    this.errorElement.textContent = this.errorMessageValue;
    this.form.prepend(this.errorElement);
  }
  clearError() {
    this.errorElement?.remove();
    this.errorElement = null;
  }
};
__publicField(captcha_controller_default, "values", {
  siteKey: String,
  action: { type: String, default: "submit" },
  errorMessage: String,
  scriptUrl: { type: String, default: "https://www.google.com/recaptcha/api.js" }
});
export {
  captcha_controller_default as default
};
