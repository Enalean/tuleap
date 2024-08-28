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
import { custom_schema } from "../../../custom_schema";

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

export function buildImagePopover(
    popover_element_id: string,
    view: EditorView,
    doc: Document,
    image_alt_id: string,
    image_src_id: string,
    popover_title_id: string,
    popover_submit_id: string,
    gettext_provider: GetText,
): HTMLElement {
    const container = doc.createElement("div");
    const trigger = buildTrigger(doc, popover_element_id, "fa-image");
    container.appendChild(trigger);

    const popover_content = doc.body.appendChild(doc.createElement("form"));
    popover_content.className = "tlp-popover";

    const arrow = popover_content.appendChild(doc.createElement("div"));
    arrow.classList.add("tlp-popover-arrow");

    popover_content.appendChild(
        getHeader(doc, popover_title_id, gettext_provider.gettext("Add an image")),
    );

    const popover_body = buildPopoverBody(image_alt_id, image_src_id, doc, gettext_provider);
    popover_content.appendChild(popover_body);

    popover_content.appendChild(getFooter(doc, popover_submit_id, gettext_provider));

    popover = createPopover(trigger, popover_content, {
        anchor: trigger,
        trigger: "click",
        placement: "bottom-start",
    });
    addListeners(popover, popover_content, view, image_alt_id, image_src_id);
    doc.body.appendChild(container);

    return trigger;
}

function buildPopoverBody(
    image_alt_id: string,
    image_src_id: string,
    doc: Document,
    gettext_provider: GetText,
): HTMLDivElement {
    const popover_body = document.createElement("div");
    popover_body.className = "tlp-popover-body";

    const src: TextField = {
        placeholder: "https://example.com",
        label: gettext_provider.gettext("Image source"),
        required: true,
        type: "url",
        focus: true,
        id: image_src_id,
        name: "input-src",
        value: "",
        pattern: "https?://.+",
    };
    const alt: TextField = {
        placeholder: gettext_provider.gettext("Title"),
        label: gettext_provider.gettext("Title"),
        type: "input",
        required: false,
        focus: false,
        id: image_alt_id,
        name: "input-text",
        value: "",
    };

    createAndInsertField([src, alt], popover_body, doc);

    return popover_body;
}

export function addListeners(
    popover: Popover,
    form: HTMLFormElement,
    view: EditorView,
    image_alt_id: string,
    image_src_id: string,
): void {
    const submit = (event: Event): boolean => {
        event.preventDefault();
        const attrs = getValues(image_alt_id, image_src_id);
        if (attrs) {
            popover.hide();
            const { state, dispatch } = view;
            const node = custom_schema.nodes.image.create(attrs);
            const transaction = state.tr.replaceSelectionWith(node);
            dispatch(transaction);
        }

        return true;
    };

    function getValues(
        image_alt_id: string,
        image_src_id: string,
    ): { src: string; alt: string; title: string } {
        const result = { src: "", alt: "", title: "" };

        const src = document.getElementById(image_src_id);
        if (src instanceof HTMLInputElement) {
            result.src = src.value;
        }
        const alt = document.getElementById(image_alt_id);
        if (alt instanceof HTMLInputElement) {
            result.alt = alt.value.trim() ? alt.value : result.src;
            result.title = alt.value.trim() ? alt.value : result.src;
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
