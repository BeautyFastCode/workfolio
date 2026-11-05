import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        id: String,
    };

    get id() {
        return this.idValue;
    }

    async scroll() {
        const element = document.getElementById(this.id);
        const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

        await sleep(100);

        element.scrollIntoView({
            behavior: 'smooth',
        });
    }
}
