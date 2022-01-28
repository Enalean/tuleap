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

import { PriorityField } from "./PriorityField";
import type { HostElement } from "./PriorityField";

describe("PriorityField", () => {
    it('When the field type is "%s", Then it displays the field and its value will be "%s"', () => {
        const host = {
            field: {
                label: "Priority",
                value: 123456,
            },
        } as unknown as HostElement;

        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const update = PriorityField.content(host);

        update(host, target);

        const label = target.querySelector("[data-test=priority-field-label]");
        const value = target.querySelector("[data-test=priority-field-value]");

        if (!(label instanceof HTMLElement) || !(value instanceof HTMLElement)) {
            throw new Error("An element is missing in PriorityField");
        }

        expect(label.textContent?.trim()).toBe("Priority");
        expect(value.textContent?.trim()).toBe("123456");
    });
});
