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

import { BurndownField } from "./BurndownField";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";

import type { HostElement } from "./BurndownField";

describe("BurndownField", () => {
    it("should render the burndown field", () => {
        const host = {
            field: {
                field_id: 60,
                label: "Burndown",
            },
            currentArtifactIdentifier: CurrentArtifactIdentifierStub.withId(1060),
        } as unknown as HostElement;

        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        const update = BurndownField.content(host);

        update(host, target);

        const label = target.querySelector("[data-test=burndown-field-label]");
        const image = target.querySelector("[data-test=burndown-field-image]");

        if (!(label instanceof HTMLElement) || !(image instanceof HTMLImageElement)) {
            throw new Error("An element is missing in BurndownField");
        }

        expect(label.textContent?.trim()).toBe("Burndown");
        expect(image.alt).toBe("Burndown");
        expect(image.src).toBe("/plugins/tracker/?formElement=60&func=show_burndown&src_aid=1060");
    });
});
