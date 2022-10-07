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

import { getTypeSelectorTemplate } from "./TypeSelectorTemplate";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./LinkField";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { FORWARD_DIRECTION } from "../../../../domain/fields/link-field/LinkType";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import { ControlLinkedArtifactsPopoversStub } from "../../../../../tests/stubs/ControlLinkedArtifactsPopoversStub";
import { selectOrThrow } from "@tuleap/dom";
import { AllowedLinksTypesCollection } from "./AllowedLinksTypesCollection";
import { VerifyIsTrackerInAHierarchyStub } from "../../../../../tests/stubs/VerifyIsTrackerInAHierarchyStub";

const getSelectMainOptionsGroup = (select: HTMLSelectElement): HTMLOptGroupElement =>
    selectOrThrow(select, "[data-test=link-type-select-optgroup]", HTMLOptGroupElement);

describe("TypeSelectorTemplate", () => {
    let host: HostElement,
        allowed_link_types: CollectionOfAllowedLinksTypesPresenters,
        cross_reference: ArtifactCrossReference | null;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        allowed_link_types =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                VerifyHasParentLinkStub.withNoParentLink(),
                AllowedLinksTypesCollection.buildFromTypesRepresentations([
                    {
                        shortname: IS_CHILD_LINK_TYPE,
                        forward_label: "Child",
                        reverse_label: "Parent",
                    },
                ])
            );
        cross_reference = ArtifactCrossReferenceStub.withRef("story #150");
    });

    const render = (): HTMLSelectElement => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
        const field: ArtifactLinkFieldStructure = {
            field_id: 276,
            type: "art_link",
            label: "Artifact link",
            allowed_types: [],
        };
        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(22);
        const fault_notifier = NotifyFaultStub.withCount();
        const notification_clearer = ClearFaultNotificationStub.withCount();
        const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(30);
        const parents_retriever = RetrievePossibleParentsStub.withoutParents();
        const link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
        const controller = LinkFieldController(
            RetrieveAllLinkedArtifactsStub.withoutLink(),
            RetrieveLinkedArtifactsSyncStub.withoutLink(),
            AddLinkMarkedForRemovalStub.withCount(),
            DeleteLinkMarkedForRemovalStub.withCount(),
            VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
            fault_notifier,
            notification_clearer,
            ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact(
                    LinkableArtifactStub.withDefaults()
                ),
                fault_notifier,
                notification_clearer,
                parents_retriever,
                link_verifier,
                current_artifact_identifier,
                current_tracker_identifier
            ),
            AddNewLinkStub.withCount(),
            DeleteNewLinkStub.withCount(),
            RetrieveNewLinksStub.withoutLink(),
            VerifyHasParentLinkStub.withNoParentLink(),
            parents_retriever,
            link_verifier,
            field,
            current_artifact_identifier,
            current_tracker_identifier,
            ArtifactCrossReferenceStub.withRef("bug #22"),
            ControlLinkedArtifactsPopoversStub.build(),
            AllowedLinksTypesCollection.buildFromTypesRepresentations(field.allowed_types),
            VerifyIsTrackerInAHierarchyStub.withNoHierarchy()
        );
        host = {
            controller,
            field_presenter: LinkFieldPresenter.fromFieldAndCrossReference(field, cross_reference),
            allowed_link_types,
            current_link_type: LinkTypeStub.buildUntyped(),
        } as HostElement;

        const updateFunction = getTypeSelectorTemplate(host);
        updateFunction(host, target);

        return selectOrThrow(target, "[data-test=link-type-select]", HTMLSelectElement);
    };

    it("should build the type selector", () => {
        const select = render();
        const optgroup = getSelectMainOptionsGroup(select);

        expect(optgroup.label).toBe("story #150");

        const options_with_label = Array.from(select.options).filter(
            (option) => option.label !== "–"
        );
        const separators = Array.from(select.options).filter((option) => option.label === "–");
        expect(separators).toHaveLength(1);
        expect(options_with_label).toHaveLength(3);

        const [untyped_option, child_option, parent_option] = options_with_label;
        expect(untyped_option.selected).toBe(true);
        expect(untyped_option.label).toBe("Linked to");
        expect(child_option.label).toBe("Child");
        expect(parent_option.label).toBe("Parent");

        expect(options_with_label.every((option) => !option.disabled)).toBe(true);
    });

    it(`disables the reverse _is_child option if marked to be disabled`, () => {
        allowed_link_types =
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                VerifyHasParentLinkStub.withParentLink(),
                AllowedLinksTypesCollection.buildFromTypesRepresentations([
                    {
                        shortname: IS_CHILD_LINK_TYPE,
                        forward_label: "Child",
                        reverse_label: "Parent",
                    },
                ])
            );
        const select = render();

        const parent_is_disabled = Array.from(select.options).some(
            (option) => option.label === "Parent" && option.disabled
        );
        expect(parent_is_disabled).toBe(true);
    });

    it("Should display 'New artifact' when there is no artifact cross reference (creation mode)", () => {
        cross_reference = null;
        const select = render();

        expect(getSelectMainOptionsGroup(select).label).toBe("New artifact");
    });

    it(`sets the current link type when there's a change in the select`, () => {
        const select = render();
        select.value = `${IS_CHILD_LINK_TYPE} ${FORWARD_DIRECTION}`;
        select.dispatchEvent(new Event("change"));

        expect(host.current_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
    });
});
