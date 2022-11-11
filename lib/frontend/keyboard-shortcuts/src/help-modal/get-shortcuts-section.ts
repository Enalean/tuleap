/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 */

import { selectOrThrow } from "@tuleap/dom";

export const getGlobalShortcutsSection = (doc: Document): HTMLElement =>
    selectOrThrow(doc, "[data-shortcuts-global-section]");

export function getSpecificShortcutsSection(doc: Document): HTMLElement {
    const shortcuts_section = doc.querySelector("[data-shortcuts-specific-section]");
    if (shortcuts_section instanceof HTMLElement) {
        return shortcuts_section;
    }
    return createSpecificShortcutsSectionInHelpModal(doc);
}

function createSpecificShortcutsSectionInHelpModal(doc: Document): HTMLElement {
    widenModalSize(doc);

    const specific_shortcuts_section = doc.createElement("section");
    specific_shortcuts_section.setAttribute("data-shortcuts-specific-section", "");
    specific_shortcuts_section.classList.add("help-modal-shortcuts-section");

    const shortcuts_modal_body = selectOrThrow(doc, "[data-shortcuts-modal-body]");
    shortcuts_modal_body.append(specific_shortcuts_section);

    return specific_shortcuts_section;
}

function widenModalSize(doc: Document): void {
    const shortcuts_modal = selectOrThrow(doc, "#help-modal-shortcuts");
    shortcuts_modal.classList.add("tlp-modal-medium-sized");
}
