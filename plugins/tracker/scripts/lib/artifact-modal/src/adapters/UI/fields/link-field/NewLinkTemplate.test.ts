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

import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./LinkField";
import { getNewLinkTemplate } from "./NewLinkTemplate";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import type { NewLink } from "../../../../domain/fields/link-field/NewLink";
import { LinkFieldController } from "./LinkFieldController";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { ArtifactLinkSelectorAutoCompleter } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import { selectOrThrow } from "@tuleap/dom";
import { AllowedLinksTypesCollection } from "./AllowedLinksTypesCollection";
import { VerifyIsTrackerInAHierarchyStub } from "../../../../../tests/stubs/VerifyIsTrackerInAHierarchyStub";
import { UserIdentifierStub } from "../../../../../tests/stubs/UserIdentifierStub";
import { RetrieveUserHistoryStub } from "../../../../../tests/stubs/RetrieveUserHistoryStub";
import { okAsync } from "neverthrow";
import { SearchArtifactsStub } from "../../../../../tests/stubs/SearchArtifactsStub";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";

describe(`NewLinkTemplate`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (link: NewLink): void => {
        const host = {} as HostElement;

        const updateFunction = getNewLinkTemplate(link);
        updateFunction(host, target);
    };

    it.each([
        [
            "open artifact",
            NewLinkStub.withDefaults(196, {
                title: "brangle",
                xref: ArtifactCrossReferenceStub.withRefAndColor("release #196", "plum-crazy"),
                uri: "/plugins/tracker/?aid=196",
                status: { value: "On Going", color: "daphne-blue" },
                is_open: true,
                link_type: LinkTypeStub.buildUntyped(),
            }),
        ],
        [
            "closed artifact",
            NewLinkStub.withDefaults(246, {
                title: "catoptrite",
                xref: ArtifactCrossReferenceStub.withRefAndColor("release #246", "plum-crazy"),
                uri: "/plugins/tracker/?aid=246",
                status: { value: "Delivered", color: "daphne-blue" },
                is_open: false,
                link_type: LinkTypeStub.buildParentLinkType(),
            }),
        ],
    ])(`will render an artifact about to be linked (a new link)`, (_type_of_link, new_link) => {
        render(new_link);

        const row = selectOrThrow(target, "[data-test=link-row]");
        const link = selectOrThrow(target, "[data-test=link-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=link-xref]");
        const title = selectOrThrow(target, "[data-test=link-title]");
        const status = selectOrThrow(target, "[data-test=link-status]");
        const type = selectOrThrow(target, "[data-test=link-type]");
        const expected_type =
            new_link.link_type.shortname === UNTYPED_LINK ? "Linked to" : new_link.link_type.label;

        expect(link.href).toBe(new_link.uri);
        expect(xref.classList.contains(`tlp-swatch-${new_link.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(new_link.xref.ref);
        expect(title.textContent?.trim()).toBe(new_link.title);
        expect(status.textContent?.trim()).toBe(new_link.status?.value);
        expect(type.textContent?.trim()).toBe(expected_type);

        expect(row.classList.contains("tlp-table-row-success")).toBe(true);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(false);
        expect(status.classList.contains("tlp-badge-daphne-blue")).toBe(true);
    });

    it(`will render an artifact without color`, () => {
        const new_link = NewLinkStub.withDefaults(246, {
            title: "catoptrite",
            xref: ArtifactCrossReferenceStub.withRefAndColor("release #246", "plum-crazy"),
            uri: "/plugins/tracker/?aid=246",
            status: { value: "Delivered", color: null },
            is_open: false,
            link_type: LinkTypeStub.buildParentLinkType(),
        });
        render(new_link);

        const row = selectOrThrow(target, "[data-test=link-row]");
        const link = selectOrThrow(target, "[data-test=link-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=link-xref]");
        const title = selectOrThrow(target, "[data-test=link-title]");
        const status = selectOrThrow(target, "[data-test=link-status]");
        const type = selectOrThrow(target, "[data-test=link-type]");

        expect(link.href).toBe(new_link.uri);
        expect(xref.classList.contains(`tlp-swatch-${new_link.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(new_link.xref.ref);
        expect(title.textContent?.trim()).toBe(new_link.title);
        expect(status.textContent?.trim()).toBe(new_link.status?.value);
        expect(type.textContent?.trim()).toBe(new_link.link_type.label);

        expect(row.classList.contains("tlp-table-row-success")).toBe(true);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(true);
    });

    describe(`action button`, () => {
        const getHost = (new_link: NewLink): HostElement => {
            const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(22);
            const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(28);
            const parents_retriever = RetrievePossibleParentsStub.withoutParents();
            const link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
            const event_dispatcher = DispatchEventsStub.buildNoOp();
            const allowed_types = [
                {
                    shortname: IS_CHILD_LINK_TYPE,
                    forward_label: "Child",
                    reverse_label: "Parent",
                },
            ];

            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withoutLink(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
                ArtifactLinkSelectorAutoCompleter(
                    RetrieveMatchingArtifactStub.withMatchingArtifact(
                        okAsync(LinkableArtifactStub.withDefaults())
                    ),
                    parents_retriever,
                    link_verifier,
                    RetrieveUserHistoryStub.withoutUserHistory(),
                    SearchArtifactsStub.withoutResults(),
                    event_dispatcher,
                    current_artifact_identifier,
                    current_tracker_identifier,
                    UserIdentifierStub.fromUserId(101)
                ),
                AddNewLinkStub.withCount(),
                DeleteNewLinkStub.withCount(),
                RetrieveNewLinksStub.withoutLink(),
                VerifyHasParentLinkStub.withNoParentLink(),
                parents_retriever,
                link_verifier,
                VerifyIsTrackerInAHierarchyStub.withNoHierarchy(),
                event_dispatcher,
                {
                    field_id: 525,
                    label: "Artifact link",
                    type: "art_link",
                    allowed_types,
                },
                current_artifact_identifier,
                current_tracker_identifier,
                ArtifactCrossReferenceStub.withRef("bug #22"),
                AllowedLinksTypesCollection.buildFromTypesRepresentations(allowed_types)
            );

            return {
                new_links_presenter: NewLinkCollectionPresenter.fromLinks([new_link]),
                controller,
            } as HostElement;
        };

        const render = (host: HostElement, new_link: NewLink): void => {
            const update = getNewLinkTemplate(new_link);
            update(host, target);
        };

        it(`will delete the new link`, () => {
            const new_link = NewLinkStub.withDefaults();
            const host = getHost(new_link);
            render(host, new_link);
            const button = selectOrThrow(target, "[data-test=action-button]", HTMLButtonElement);
            button.click();

            expect(host.new_links_presenter).toHaveLength(0);
        });
    });
});
