var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

// assets/src/controllers/autocomplete_search_controller.js
import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";
import { visit } from "@hotwired/turbo";
var autocomplete_search_controller_default = class extends Controller {
  connect() {
    this.loadedItems = false;
    this.tomSelect = new TomSelect(this.element, {
      valueField: "url",
      labelField: "text",
      searchField: "text",
      openOnFocus: true,
      allowEmptyOption: true,
      shouldLoad: function(query, callback) {
        return true;
      },
      // fetch remote data
      load: (query, callback) => {
        const url = this.urlValue + "?q=" + encodeURIComponent(query);
        fetch(url).then((response) => response.json()).then((json) => {
          callback(json.items);
        }).catch(() => {
          callback();
        });
      },
      // custom rendering functions for options and items
      render: {
        option: (item, escape) => {
          this.loadedItems = true;
          return `<div class="py-2 d-flex gap-3">
                                <div class="icon">
                                    <i class="${escape(item.icon)} ${escape(item.icon_color)}"></i>
                                </div>
                                <div>${escape(item.text)}</div>
						    </div>`;
        },
        //this is the value shown in the input text box after selection
        item: function(item, escape) {
          return `<span>${escape(item.text.slice(0, 20))} ..</span>`;
        }
      },
      onItemAdd(value, item) {
        visit(value, { frame: "_top" });
      },
      onFocus() {
        if (Object.keys(this.options).length === 0) {
          this.load("");
        }
      }
    });
  }
  disconnect() {
    this.tomSelect?.destroy();
    this.tomSelect = null;
  }
};
__publicField(autocomplete_search_controller_default, "values", { url: String });
export {
  autocomplete_search_controller_default as default
};
