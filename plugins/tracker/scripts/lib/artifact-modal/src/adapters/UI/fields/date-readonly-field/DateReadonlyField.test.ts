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

import { DateReadonlyField } from "./DateReadonlyField";
import type { HostElement } from "./DateReadonlyField";
import { FormatReadonlyDateFieldStub } from "../../../../../tests/stubs/FormatReadonlyDateFieldStub";

describe("DateReadonlyField", () => {
    it("should render the readonly date field", () => {
        const host = {
            field: {
                label: "Submitted on",
                value: "2022-01-31T10:30:00Z",
            },
            formatter: FormatReadonlyDateFieldStub.withFormattedDateString("2022-01-31 10:30"),
        } as unknown as HostElement;

        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const update = DateReadonlyField.content(host);

        update(host, target);

        const label = target.querySelector("[data-test=date-readonly-field-label]");
        const date = target.querySelector("[data-test=date-readonly-field-date]");

        if (!(label instanceof HTMLElement) || !(date instanceof HTMLElement)) {
            throw new Error("An element is missing in DateReadonlyField");
        }

        expect(label.textContent?.trim()).toBe("Submitted on");
        expect(date.textContent?.trim()).toBe("2022-01-31 10:30");
    });
});
