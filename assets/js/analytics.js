export function track(event, payload) {
    if (!window.umami || !event) {
        return;
    }

    window.umami?.track(event, payload);
}
