import {Controller} from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import {visit} from "@hotwired/turbo";
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {url: String};

    connect() {

        this.loadedItems = false;

        this.tomSelect = new TomSelect(this.element, {
            valueField: 'url',
            labelField: 'text',
            searchField: 'text',
            openOnFocus: true,
            allowEmptyOption: true,
            shouldLoad: function (query, callback) {
                return true;
            },
            // fetch remote data
            load: (query, callback) => {

                const url = this.urlValue + '?q=' + encodeURIComponent(query);

                fetch(url)
                    .then(response => response.json())
                    .then(json => {
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
                item: function (item, escape) {
                    return `<span>${escape(item.text.slice(0, 20))} ..</span>`;
                },
            },
            onItemAdd(value, item) {
                visit(value, {frame: '_top'});
            },
            onFocus() {

                //if no results have been loaded, perform a search with empty query
                if (Object.keys(this.options).length === 0) {
                    this.load('');
                }
            }

        });

    }

    disconnect() {
        this.tomSelect?.destroy();
        this.tomSelect = null;

    }
}
