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

import { selectOrThrow } from "@tuleap/dom";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { Option } from "@tuleap/option";
import type { HostElement } from "./LinkTypeSelectorElement";
import { LinkTypeSelectorElement } from "./LinkTypeSelectorElement";
import { setCatalog } from "../../../../gettext-catalog";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { FORWARD_DIRECTION, LinkType } from "../../../../domain/fields/link-field/LinkType";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { LinkTypesCollectionStub } from "../../../../../tests/stubs/LinkTypesCollectionStub";

const getSelectMainOptionsGroup = (select: HTMLSelectElement): HTMLOptGroupElement =>
    selectOrThrow(select, "[data-test=link-type-select-optgroup]", HTMLOptGroupElement);

describe("LinkTypeSelectorElement", () => {
    let allowed_link_types: CollectionOfAllowedLinksTypesPresenters,
        cross_reference: Option<ArtifactCrossReference>,
        doc: Document;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
        allowed_link_types =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                false,
                LinkTypesCollectionStub.withParentPair(),
            );
        cross_reference = Option.fromValue(ArtifactCrossReferenceStub.withRef("story #150"));
    });

    const getHost = (): HostElement => {
        const element = doc.createElement("span");
        return Object.assign(element, {
            value: LinkTypeStub.buildUntyped(),
            disabled: false,
            current_artifact_reference: cross_reference,
            available_types: allowed_link_types,
        }) as HostElement;
    };

    const render = (host: HostElement): HTMLSelectElement => {
        const update = LinkTypeSelectorElement.content(host);
        update(host, host);
        return selectOrThrow(host, "[data-test=link-type-select]", HTMLSelectElement);
    };

    it("should build the type selector", () => {
        const select = render(getHost());
        const optgroup = getSelectMainOptionsGroup(select);

        expect(optgroup.label).toBe("story #150");

        const options_with_label = Array.from(select.options).filter(
            (option) => option.label !== "–",
        );
        const separators = Array.from(select.options).filter((option) => option.label === "–");
        expect(separators).toHaveLength(1);
        expect(options_with_label).toHaveLength(3);

        const [untyped_option, parent_option, child_option] = options_with_label;
        expect(untyped_option.selected).toBe(true);
        expect(untyped_option.label).toBe("is Linked to");
        expect(parent_option.label).toBe("is Parent of");
        expect(child_option.label).toBe("is Child of");

        expect(options_with_label.every((option) => !option.disabled)).toBe(true);
    });

    it(`disables the reverse _is_child option and display the reason if marked to be disabled`, () => {
        allowed_link_types =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                true,
                LinkTypesCollectionStub.withParentPair(),
            );
        const select = render(getHost());

        const child_of_is_disabled = Array.from(select.options).find(
            (option) => option.value === "_is_child reverse",
        );

        if (!child_of_is_disabled) {
            throw new Error("child_of_disabled should not be undefined");
        }
        expect(child_of_is_disabled.disabled).toBe(true);
        expect(child_of_is_disabled.title).not.toHaveLength(0);
    });

    it("Should display 'New artifact' when there is no artifact cross reference (creation mode)", () => {
        cross_reference = Option.nothing();
        const select = render(getHost());

        expect(getSelectMainOptionsGroup(select).label).toBe("New artifact");
    });

    it(`will dispatch a bubbling type-changed event when there's a change in the select`, () => {
        const host = getHost();
        const dispatchEvent = jest.spyOn(host, "dispatchEvent");
        const select = render(host);
        select.value = `${IS_CHILD_LINK_TYPE} ${FORWARD_DIRECTION}`;
        select.dispatchEvent(new Event("change"));

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("type-changed");
        expect(event.bubbles).toBe(true);
        if (!(event instanceof CustomEvent)) {
            throw Error("Expected a custom event");
        }
        expect(LinkType.isForwardChild(event.detail.new_link_type)).toBe(true);
    });

    it(`dispatches a bubbling "change" event when its selectbox is changed
        so that the modal shows a warning when closed`, () => {
        const host = getHost();
        const select = render(host);
        let is_bubbling = false;
        host.addEventListener("change", (event) => {
            is_bubbling = event.bubbles;
        });
        select.value = `${IS_CHILD_LINK_TYPE} ${FORWARD_DIRECTION}`;
        select.dispatchEvent(new Event("change", { bubbles: true }));

        expect(is_bubbling).toBe(true);
    });
});
