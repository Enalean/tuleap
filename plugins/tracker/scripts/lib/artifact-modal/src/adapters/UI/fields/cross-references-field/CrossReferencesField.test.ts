/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { setCatalog } from "../../../../gettext-catalog";
import { CrossReferencesField } from "./CrossReferencesField";

import type { HostElement, CrossReference } from "./CrossReferencesField";

const field_label = "References";

function getHost(values: CrossReference[] = []): HostElement {
    return {
        field: {
            label: field_label,
            value: values,
        },
    } as unknown as HostElement;
}

describe("CrossReferencesField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    it("When there is no cross-reference, then it displays an empty state", () => {
        const host = getHost();
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const update = CrossReferencesField.content(host);

        update(host, target);

        const label = target.querySelector("[data-test=cross-references-field-label]");
        const empty_state = target.querySelector("[data-test=cross-references-field-empty-state]");

        if (!(label instanceof HTMLElement)) {
            throw new Error("label is missing in CrossReferencesField");
        }

        expect(label.textContent?.trim()).toBe(field_label);
        expect(empty_state).not.toBeNull();
    });

    it("When there are cross-references, then it displays them", () => {
        const host = getHost([
            { url: "https://url/to/art/1", ref: "art #1" },
            { url: "https://url/to/art/2", ref: "art #2" },
        ]);
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const update = CrossReferencesField.content(host);

        update(host, target);

        const references: Array<HTMLAnchorElement> = Array.from(
            target.querySelectorAll("[data-test=cross-references-field-cross-reference-link]"),
        );

        expect(references).toHaveLength(2);

        expect(references[0].href).toBe("https://url/to/art/1");
        expect(references[0].textContent?.trim()).toBe("art #1");

        expect(references[1]?.href).toBe("https://url/to/art/2");
        expect(references[1]?.textContent?.trim()).toBe("art #2");
    });
});
