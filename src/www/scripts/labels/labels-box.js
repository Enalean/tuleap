import { get, patch } from "tlp-fetch";
import jQuery from "jquery";

export async function create(
    container,
    labels_endpoint,
    available_labels_endpoint,
    is_update_allowed
) {
    const existing_labels = await getRecursively(labels_endpoint, [], 0);

    initiateSelect2(
        container,
        existing_labels,
        labels_endpoint,
        available_labels_endpoint,
        is_update_allowed
    );
}

async function getRecursively(labels_endpoint, labels, offset) {
    const limit = 50;
    offset = encodeURIComponent(offset);

    const response = await get(`${labels_endpoint}/?&limit=${limit}&offset=${offset}`);
    const json = await response.json();
    json.labels.forEach(item => labels.push(convertLabelToSelect2Entry(item)));

    const is_recursion_needed =
        offset + limit < parseInt(response.headers.get("X-PAGINATION-SIZE"), 10);
    if (is_recursion_needed) {
        labels = await getRecursively(labels_endpoint, labels, offset + limit);
    }

    return labels;
}

const convertLabelToSelect2Entry = ({ id, label, is_outline, color }) => ({
    id,
    text: label,
    is_outline,
    color
});

function initiateSelect2(
    container,
    existing_labels,
    labels_endpoint,
    available_labels_endpoint,
    is_update_allowed
) {
    const input = createHiddenInput(container, existing_labels);
    jQuery(input)
        .select2({
            tags: true,
            multiple: true,
            tokenSeparators: [",", "\t"],
            containerCssClass: "item-labels-box-select2",
            dropdownCssClass: "item-labels-box-select2-results",
            searchInputPlaceholder: window.codendi.getText("labels-box", "Add labels"),
            ajax: {
                url: available_labels_endpoint,
                dataType: "json",
                quietMillis: 250,
                cache: true,
                data: term => ({ query: term }),
                results: data => ({ results: data.labels.map(convertLabelToSelect2Entry) })
            },
            initSelection: (element, callback) => callback(existing_labels),
            createSearchChoice: (term, data) => {
                const data_that_matches_term = data.filter(
                    ({ text }) =>
                        text.localeCompare(term, undefined, { sensitivity: "accent" }) === 0
                );

                if (data_that_matches_term.length === 0) {
                    return {
                        id: term,
                        text: term,
                        is_outline: true,
                        color: "chrome-silver"
                    };
                }
            },
            formatSelection: ({ is_outline, color, text }, container, escapeMarkup) => {
                container.prevObject[0].classList.add(`select-item-label-color-${color}`);
                if (is_outline) {
                    container.prevObject[0].classList.add("select-item-label-outline");
                }

                return escapeMarkup(text);
            },
            formatResult: ({ is_outline, text }, container, query, escapeMarkup) => {
                const bullet_class = is_outline ? "fa-circle-o" : "fa-circle";
                const escaped_text = escapeMarkup(text);

                return `<span class="select-item-label-title">
                    <i class="select-item-label-bullet fa ${bullet_class}"></i>${escaped_text}
                </span>`;
            },
            formatResultCssClass: ({ color }) => {
                return `select-item-label-color-${color}`;
            }
        })
        .select2("enable", is_update_allowed)
        .on("change", event => {
            if (event.removed) {
                removeLabel(labels_endpoint, event.removed);
            }
            if (event.added) {
                addLabel(labels_endpoint, event.added);
            }
        })
        .on("select2-close", closeInput);
}

async function removeLabel(labels_endpoint, { id }) {
    const label_id = parseInt(id);

    if (!label_id) {
        return;
    }

    const label = { id: label_id };

    await patch(labels_endpoint, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ remove: [label] })
    });
    closeInput();
}

async function addLabel(labels_endpoint, { id, text }) {
    const label_id = parseInt(id);
    const label = label_id ? { id: label_id } : { label: text };

    await patch(labels_endpoint, {
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ add: [label] })
    });
    closeInput();
}

function closeInput() {
    setTimeout(function() {
        const input_focused = document.querySelector(".select2-focused");

        if (input_focused) {
            input_focused.blur();
        }
    }, 5);
}

function createHiddenInput(container, existing_labels) {
    const input = document.createElement("input");
    input.setAttribute("type", "hidden");
    if (existing_labels.length > 0) {
        input.setAttribute("value", JSON.stringify(existing_labels));
    }

    container.appendChild(input);

    return input;
}
