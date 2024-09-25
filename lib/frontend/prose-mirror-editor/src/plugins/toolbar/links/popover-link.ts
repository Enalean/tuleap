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
import { createAndInsertField } from "../popover/fields-adder";
import type { GetText } from "@tuleap/gettext";
import { buildTrigger, getFooter, getHeader } from "../popover/common-builder";
import type { LinkProperties } from "../../../types/internal-types";
import { replaceLinkNode } from "../../../helpers/replace-link-node";

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
    const trigger = buildTrigger(doc, popover_element_id, "fa-link");
    container.appendChild(trigger);

    const popover_content = doc.body.appendChild(doc.createElement("form"));
    popover_content.className = "tlp-popover";

    const arrow = popover_content.appendChild(doc.createElement("div"));
    arrow.classList.add("tlp-popover-arrow");

    popover_content.appendChild(
        getHeader(doc, popover_title_id, gettext_provider.gettext("Create a link")),
    );

    const popover_body = buildPopoverBody(link_title_id, link_href_id, doc, gettext_provider);
    popover_content.appendChild(popover_body);

    popover_content.appendChild(getFooter(doc, popover_submit_id, gettext_provider));

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

export function addListeners(
    popover: Popover,
    form: HTMLFormElement,
    view: EditorView,
    link_title_id: string,
    link_href_id: string,
): void {
    const submit = (event: Event): boolean => {
        event.preventDefault();
        const attrs = getLinkProperties(link_title_id, link_href_id);
        if (attrs) {
            popover.hide();
            replaceLinkNode(view, attrs);
        }

        return true;
    };

    function getLinkProperties(link_title_id: string, link_href_id: string): LinkProperties {
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
