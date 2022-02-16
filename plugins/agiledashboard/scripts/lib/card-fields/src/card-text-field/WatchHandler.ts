/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { Classifier } from "../highlight/Classifier";
import { getHighlightedDOM, getHighlightedNodes } from "./dom-modifier";
import type { PurifyHTML } from "./PurifyHTML";

export interface RequestAnimationFrame {
    requestAnimationFrame(callback: () => void): void;
}

export interface CardField {
    readonly format: "html" | "text";
    readonly value: string;
}

type ChangeArray = [string, CardField];

export interface WatchHandlerType {
    init(card_field: CardField): void;
    onWatch(new_values: ChangeArray, old_values: ChangeArray): void;
}

const HTML_FORMAT = "html";

export const WatchHandler = (
    doc: Document,
    win: RequestAnimationFrame,
    target_element: Element,
    purifier: PurifyHTML
): WatchHandlerType => ({
    init(card_field: CardField): void {
        if (card_field.format === HTML_FORMAT) {
            const card_field_dom = purifier.sanitize(card_field.value);
            const clone = card_field_dom.cloneNode(true);
            win.requestAnimationFrame(() => {
                target_element.replaceChildren(...clone.childNodes);
            });
            return;
        }
        const text_node = new Text(card_field.value);
        win.requestAnimationFrame(() => {
            target_element.replaceChildren(text_node);
        });
    },

    onWatch(new_values: ChangeArray, old_values: ChangeArray): void {
        const [new_search, new_card_field] = new_values;
        const [old_search, old_card_field] = old_values;

        const filter_has_changed = new_search !== old_search;
        const card_field_has_changed = new_card_field !== old_card_field;
        if (!filter_has_changed && !card_field_has_changed) {
            return;
        }
        if (card_field_has_changed && new_card_field.format === HTML_FORMAT) {
            purifier.invalidate();
        }

        const classifier = Classifier(new_search);
        if (new_card_field.format === HTML_FORMAT) {
            const card_field_dom = purifier.sanitize(new_card_field.value);
            const modified_dom = getHighlightedDOM(doc, card_field_dom, classifier);
            win.requestAnimationFrame(() => {
                target_element.replaceChildren(...modified_dom.childNodes);
            });
            return;
        }

        const highlighted_nodes = getHighlightedNodes(doc, classifier, new_card_field.value);
        win.requestAnimationFrame(() => {
            target_element.replaceChildren(...highlighted_nodes);
        });
    },
});
