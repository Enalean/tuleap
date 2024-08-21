/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { Popover } from "@tuleap/tlp-popovers";
import { createPopover } from "@tuleap/tlp-popovers";
import type { EditorView } from "prosemirror-view";
import { createAndInsertField } from "./fields-adder";
import type { GetText } from "@tuleap/gettext";

export type TextField = {
    id: string;
    value: string;
    label: string;
    name: string;
    placeholder: string;
    required: boolean;
    type: string;
    focus: boolean;
    pattern?: string;
};

let popover: Popover | null = null;

export function buildPopover(
    popover_element_id: string,
    view: EditorView,
    doc: Document,
    link_title_id: string,
    link_href_id: string,
    popover_title_id: string,
    popover_submit_id: string,
    gettext_provider: GetText,
): HTMLElement {
    const container = doc.createElement("div");
    const trigger = buildTrigger(popover_element_id);
    container.appendChild(trigger);

    const popover_content = doc.body.appendChild(doc.createElement("form"));
    popover_content.className = "tlp-popover";

    const arrow = popover_content.appendChild(doc.createElement("div"));
    arrow.classList.add("tlp-popover-arrow");

    popover_content.appendChild(getHeader(popover_title_id, gettext_provider));

    const popover_body = buildPopoverBody(link_title_id, link_href_id, doc, gettext_provider);
    popover_content.appendChild(popover_body);

    popover_content.appendChild(getFooter(popover_submit_id, gettext_provider));

    popover = createPopover(trigger, popover_content, {
        anchor: trigger,
        trigger: "click",
        placement: "bottom-start",
    });
    addListeners(popover, popover_content, view, link_title_id, link_href_id);
    doc.body.appendChild(container);

    return trigger;
}

function buildPopoverBody(
    link_title_id: string,
    link_href_id: string,
    doc: Document,
    gettext_provider: GetText,
): HTMLDivElement {
    const popover_body = document.createElement("div");
    popover_body.className = "tlp-popover-body";

    const href: TextField = {
        placeholder: "https://example.com",
        label: gettext_provider.gettext("Link"),
        required: true,
        type: "url",
        focus: true,
        id: link_href_id,
        name: "input-href",
        value: "",
        pattern: "https?://.+",
    };
    const title: TextField = {
        placeholder: gettext_provider.gettext("Text"),
        label: gettext_provider.gettext("Text"),
        type: "input",
        required: false,
        focus: false,
        id: link_title_id,
        name: "input-text",
        value: "",
    };

    createAndInsertField([href, title], popover_body, doc);

    return popover_body;
}

function getHeader(popover_title_id: string, gettext_provider: GetText): HTMLDivElement {
    const popover_title_div = document.createElement("div");
    popover_title_div.className = "tlp-popover-header";
    const popover_title = document.createElement("h1");
    popover_title.textContent = gettext_provider.gettext("Create a link");
    popover_title.id = popover_title_id;
    popover_title.className = "tlp-popover-title";
    popover_title_div.appendChild(popover_title);
    return popover_title_div;
}

function getFooter(popover_sumbit_id: string, gettext_provider: GetText): HTMLDivElement {
    const submit_button = document.createElement("button");
    submit_button.type = "submit";
    submit_button.className = "tlp-button-primary tlp-button-small";
    submit_button.id = popover_sumbit_id;
    submit_button.textContent = gettext_provider.gettext("Create");

    const cancel_button = document.createElement("button");
    cancel_button.type = "button";
    cancel_button.className = "tlp-button-primary tlp-button-outline tlp-button-small";
    cancel_button.textContent = gettext_provider.gettext("Cancel");
    cancel_button.dataset.dismiss = "popover";

    const footer = document.createElement("div");
    footer.className = "tlp-popover-footer";
    footer.appendChild(cancel_button);
    footer.appendChild(document.createTextNode(" "));
    footer.appendChild(submit_button);
    return footer;
}

export function buildTrigger(popover_id: string): HTMLElement {
    const trigger = document.createElement("i");
    trigger.classList.add("fa-solid", "fa-link", "ProseMirror-icon");
    trigger.id = "trigger-popover-" + popover_id;

    return trigger;
}

export function addListeners(
    popover: Popover,
    form: HTMLFormElement,
    view: EditorView,
    link_title_id: string,
    link_href_id: string,
): void {
    const submit = (event: Event): boolean => {
        event.preventDefault();
        const attrs = getValues(link_title_id, link_href_id);
        if (attrs) {
            popover.hide();
            const schema = view.state.schema;
            const node = schema.text(attrs.title, [schema.marks.link.create(attrs)]);
            view.dispatch(view.state.tr.replaceSelectionWith(node, false));
            view.focus();
        }

        return true;
    };

    function getValues(
        link_title_id: string,
        link_href_id: string,
    ): { href: string; title: string } {
        const result = { href: "", title: "" };

        const href = document.getElementById(link_href_id);
        if (href instanceof HTMLInputElement) {
            result.href = href.value;
        }
        const title = document.getElementById(link_title_id);
        if (title instanceof HTMLInputElement) {
            result.title = title.value.trim() ? title.value : result.href;
        }

        return result;
    }

    form.addEventListener("submit", submit);

    form.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            e.preventDefault();
            close();
        } else if (e.key === "Enter" && !(e.ctrlKey || e.metaKey || e.shiftKey)) {
            e.preventDefault();
            return submit(e);
        }
    });
}
