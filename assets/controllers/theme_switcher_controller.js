import { Controller } from '@hotwired/stimulus';
import { track } from 'app-analytics';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        const savedTheme = localStorage.getItem('theme') || 'dark';
        this.applyTheme(savedTheme);
    }

    toggle() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        this.applyTheme(newTheme);
        localStorage.setItem('theme', newTheme);

        track('theme_switcher_button', { theme: newTheme });
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
}
