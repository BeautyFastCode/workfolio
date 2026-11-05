import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.onVisit = this.markActiveNavLink.bind(this);
        document.addEventListener('turbo:visit', this.onVisit);
    }

    disconnect() {
        document.removeEventListener('turbo:visit', this.onVisit);
    }
    markActiveNavLink() {
        const path = window.location.pathname;

        if (path === '/') {
            return;
        }

        this.element.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');

            const url = new URL(link.href);

            if (url.pathname === path) {
                link.classList.add('active');
            }
        });
    }
}
