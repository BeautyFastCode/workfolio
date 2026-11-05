import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.onLoad = this.updatePageTitle.bind(this);
        document.addEventListener('turbo:frame-load', this.onLoad);
    }

    disconnect() {
        document.removeEventListener('turbo:frame-load', this.onLoad);
    }

    updatePageTitle(event) {
        const el = event.target.querySelector('[data-page-title]');

        if (!el) {
            return;
        }

        document.title = el.dataset.pageTitle;
    }
}
