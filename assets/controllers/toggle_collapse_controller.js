import { Controller } from '@hotwired/stimulus';
import { track } from 'app-analytics';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['icon', 'label'];
    static values = {
        showMoreLabel: String,
        showLessLabel: String,
    };

    get icon() {
        return this.iconTarget;
    }

    get label() {
        return this.labelTarget;
    }

    get showMoreLabel() {
        return this.showMoreLabelValue;
    }

    get showLessLabel() {
        return this.showLessLabelValue;
    }

    connect() {
        this.onShow = this.open.bind(this);
        this.onHide = this.close.bind(this);

        this.element.addEventListener('shown.bs.collapse', this.onShow);
        this.element.addEventListener('hidden.bs.collapse', this.onHide);
    }

    disconnect() {
        this.element.removeEventListener('shown.bs.collapse', this.onShow);
        this.element.removeEventListener('hidden.bs.collapse', this.onHide);
    }

    open() {
        this.icon.classList.remove('fa-chevron-down');
        this.icon.classList.add('fa-chevron-up');

        this.label.textContent = this.showLessLabel;

        track('toggle_collapse', { action: 'opened' });
    }

    close() {
        this.icon.classList.remove('fa-chevron-up');
        this.icon.classList.add('fa-chevron-down');

        this.label.textContent = this.showMoreLabel;

        track('toggle_collapse', { action: 'closed' });
    }
}
