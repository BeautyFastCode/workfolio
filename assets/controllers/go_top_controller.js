import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.showHide = this.showHide.bind(this);
        window.addEventListener('scroll', this.showHide);
    }

    disconnect() {
        window.removeEventListener('scroll', this.showHide);
    }

    scroll() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }

    showHide() {
        const btn = this.element;

        if (window.scrollY >= 250) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    }
}
