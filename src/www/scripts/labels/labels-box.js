import '../polyphills/promise-polyfill.js';
import { get } from 'tlp-fetch';
import { sanitize } from 'dompurify';

export function create(element, labels_endpoint) {
    buildLabelsRecursively(element, labels_endpoint, 0);
}

async function buildLabelsRecursively(element, labels_endpoint, offset) {
    const limit = 50;

    try {
        const response = await get(`${labels_endpoint}/?limit=${limit}&offset=${offset}`);
        const json     = await response.json();
        json.labels.forEach(
            label => element.appendChild(buildLabelElement(label))
        );

        const is_recursion_needed = offset + limit < response.headers.get('X-PAGINATION-SIZE');
        if (is_recursion_needed) {
            buildLabelsRecursively(element, labels_endpoint, offset + limit)
        }
    } catch (e) {
        // silently ignore errors
    }
}

function buildLabelElement(label) {
    return sanitize(
        `<span class="item-label badge tlp-badge-primary tlp-badge-outline">
            <i class="icon-tags fa fa-tags"></i> ${label.label}
        </span>`,
        { RETURN_DOM_FRAGMENT: true }
    );
}
