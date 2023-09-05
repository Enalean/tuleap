/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { Modal } from "./modal";
import { createModal } from "./modal";

export function openTargetModalIdOnClick(
    doc: Document,
    button_id: string,
    beforeModalOpenCallback?: (clicked_button: HTMLElement) => void,
): Modal | null {
    const button = doc.getElementById(button_id);
    if (!button || !(button instanceof HTMLElement)) {
        return null;
    }
    const modal = getTargetModal(doc, button);
    button.addEventListener("click", () => {
        if (beforeModalOpenCallback !== undefined) {
            beforeModalOpenCallback(button);
        }
        modal.show();
    });

    return modal;
}

export function openAllTargetModalsOnClick(doc: Document, buttons_selector: string): void {
    const buttons = doc.querySelectorAll(buttons_selector);
    for (const button of buttons) {
        if (!(button instanceof HTMLElement)) {
            continue;
        }
        const modal = getTargetModal(doc, button);
        button.addEventListener("click", () => {
            modal.show();
        });
    }
}

export function getTargetModal(doc: Document, button: HTMLElement): Modal {
    if (!button.dataset.targetModalId) {
        throw new Error("Missing data-target-modal-id attribute on button");
    }
    const modal_element = doc.getElementById(button.dataset.targetModalId);
    if (!modal_element) {
        throw new Error("Could not find the element referenced by data-target-modal-id");
    }
    return createModal(doc, modal_element);
}

export interface HiddenInputReplacement {
    input_id: string;
    hiddenInputReplaceCallback: (clicked_button: HTMLElement) => string;
}

export interface ParagraphReplacement {
    paragraph_id: string;
    paragraphReplaceCallback: (clicked_button: HTMLElement) => string;
}

export interface ModalReplacementOptions {
    document: Document;
    buttons_selector: string;
    modal_element_id: string;
    hidden_input_replacement: HiddenInputReplacement;
    paragraph_replacement: ParagraphReplacement;
}

export function openModalAndReplacePlaceholders(options: ModalReplacementOptions): void {
    const buttons = options.document.querySelectorAll(options.buttons_selector);
    for (const button of buttons) {
        if (!(button instanceof HTMLElement)) {
            continue;
        }
        button.addEventListener("click", () => {
            replaceParagraph(options.document, button, options.paragraph_replacement);
            replaceHiddenInput(options.document, button, options.hidden_input_replacement);
            createAndShowModal(options.document, options.modal_element_id);
        });
    }
}

function replaceHiddenInput(
    doc: Document,
    clicked_button: HTMLElement,
    replacer: HiddenInputReplacement,
): void {
    const hidden_input = doc.getElementById(replacer.input_id);
    if (!hidden_input || !(hidden_input instanceof HTMLInputElement)) {
        throw new Error("Missing input hidden " + replacer.input_id);
    }
    hidden_input.value = replacer.hiddenInputReplaceCallback(clicked_button);
}

function replaceParagraph(
    doc: Document,
    clicked_button: HTMLElement,
    replacer: ParagraphReplacement,
): void {
    const paragraph = doc.getElementById(replacer.paragraph_id);
    if (!paragraph) {
        throw new Error("Missing paragraph in modal " + replacer.paragraph_id);
    }
    paragraph.textContent = replacer.paragraphReplaceCallback(clicked_button);
}

function createAndShowModal(doc: Document, modal_element_id: string): void {
    const modal_element = doc.getElementById(modal_element_id);
    if (!modal_element) {
        throw new Error("Missing modal element " + modal_element_id);
    }

    const modal = createModal(doc, modal_element, { destroy_on_hide: true, keyboard: true });
    modal.show();
}
