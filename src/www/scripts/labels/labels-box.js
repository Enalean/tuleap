import { sanitize } from 'dompurify';
import { getJSON } from 'jquery';

export function create(element, labels_endpoint) {
    buildLabelsRecursively(element, labels_endpoint, 0);
}

function buildLabelsRecursively(element, labels_endpoint, offset) {
    const limit = 50;

    getJSON(labels_endpoint, {limit, offset})
        .success(
            (result, status, xhr) => {
                result.labels.forEach(
                    label => element.appendChild(buildLabelElement(label))
                );

                if (offset + limit < xhr.getResponseHeader('X-PAGINATION-SIZE')) {
                    buildLabelsRecursively(element, labels_endpoint, offset + limit)
                }
            }
        );
}

function buildLabelElement(label) {
    return sanitize(
        `<span class="item-label badge tlp-badge-primary tlp-badge-outline">
            <i class="icon-tags fa fa-tags"></i> ${label.label}
        </span>`,
        { RETURN_DOM_FRAGMENT: true }
    );
}
