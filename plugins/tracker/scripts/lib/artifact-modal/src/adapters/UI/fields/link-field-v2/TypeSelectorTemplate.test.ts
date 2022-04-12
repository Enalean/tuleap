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

import type { UpdateFunction } from "hybrids";
import { getTypeSelectorTemplate } from "./TypeSelectorTemplate";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement, LinkField } from "./LinkField";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";

function getSelectMainOptionsGroup(target: ShadowRoot): HTMLOptGroupElement {
    const optgroup = target.querySelector("[data-test=link-type-select-optgroup]");
    if (!(optgroup instanceof HTMLOptGroupElement)) {
        throw new Error("The main <optgroup> can't be found in the target");
    }
    return optgroup;
}

describe("TypeSelectorTemplate", () => {
    let target: ShadowRoot, cross_reference: ArtifactCrossReference | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        cross_reference = ArtifactCrossReferenceStub.withRef("story #150");
    });

    const getTemplate = (): UpdateFunction<LinkField> => {
        const allowed_types = [
            {
                shortname: "_is_child",
                forward_label: "Child",
                reverse_label: "Parent",
            },
            {
                shortname: "_covered_by",
                forward_label: "Covered by",
                reverse_label: "Covers",
            },
        ];
        return getTypeSelectorTemplate(
            LinkFieldPresenter.fromFieldAndCrossReference(
                {
                    field_id: 276,
                    type: "art_link",
                    label: "Artifact link",
                    allowed_types,
                },
                cross_reference
            )
        );
    };

    it("should build the type selector", () => {
        const host = {} as HostElement;
        const update = getTemplate();
        update(host, target);

        const select = target.querySelector("[data-test=link-type-select]");
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Unable to find the link type select in the target");
        }

        const optgroup = getSelectMainOptionsGroup(target);
        const options = optgroup.querySelectorAll("[data-test=link-type-select-option]");

        expect(optgroup.label).toBe("story #150");
        expect(options).toHaveLength(2);

        expect(options[0].textContent?.trim()).toBe("Linked to");
        expect(options[0].hasAttribute("selected")).toBe(true);

        expect(options[1].textContent?.trim()).toBe("Child");
    });

    it("Should display 'New artifact' when there is no artifact cross reference (creation mode)", () => {
        cross_reference = null;
        const update = getTemplate();
        update({} as HostElement, target);

        expect(getSelectMainOptionsGroup(target).label).toBe("New artifact");
    });
});
