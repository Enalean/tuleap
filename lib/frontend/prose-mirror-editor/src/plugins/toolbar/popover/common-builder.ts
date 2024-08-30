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

import type { GetText } from "@tuleap/gettext";

export function getHeader(doc: Document, popover_title_id: string, title: string): HTMLDivElement {
    const popover_title_div = doc.createElement("div");
    popover_title_div.className = "tlp-popover-header";
    const popover_title = doc.createElement("h1");
    popover_title.textContent = title;
    popover_title.id = popover_title_id;
    popover_title.className = "tlp-popover-title";
    popover_title_div.appendChild(popover_title);
    return popover_title_div;
}

export function getFooter(
    doc: Document,
    popover_sumbit_id: string,
    gettext_provider: GetText,
): HTMLDivElement {
    const submit_button = doc.createElement("button");
    submit_button.type = "submit";
    submit_button.className = "tlp-button-primary tlp-button-small";
    submit_button.id = popover_sumbit_id;
    submit_button.textContent = gettext_provider.gettext("Create");

    const cancel_button = doc.createElement("button");
    cancel_button.type = "button";
    cancel_button.className = "tlp-button-primary tlp-button-outline tlp-button-small";
    cancel_button.textContent = gettext_provider.gettext("Cancel");
    cancel_button.dataset.dismiss = "popover";

    const footer = doc.createElement("div");
    footer.className = "tlp-popover-footer";
    footer.appendChild(cancel_button);
    footer.appendChild(doc.createTextNode(" "));
    footer.appendChild(submit_button);
    return footer;
}

export function buildTrigger(doc: Document, popover_id: string, icon: string): HTMLElement {
    const trigger = doc.createElement("i");
    trigger.classList.add("fa-solid", icon, "ProseMirror-icon");
    trigger.id = "trigger-popover-" + popover_id;

    return trigger;
}
