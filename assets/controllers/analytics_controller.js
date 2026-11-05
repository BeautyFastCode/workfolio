import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        event: String,
        payload: Object,
    };

    get event() {
        return this.eventValue;
    }

    get payload() {
        return this.payloadValue;
    }

    track() {
        if (!window.umami) {
            return;
        }

        if (!this.event) {
            return;
        }

        window.umami?.track(this.event, this.payload);
    }
}
