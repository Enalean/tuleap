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

import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";
import type { HostElement } from "./LinkField";
import { getActionButton, getLinkedArtifactTemplate } from "./LinkedArtifactTemplate";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { setCatalog } from "../../../../gettext-catalog";
import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";
import type { LinkedArtifact } from "../../../../domain/fields/link-field/LinkedArtifact";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { ArtifactLinkSelectorAutoCompleter } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import { VerifyIsTrackerInAHierarchyStub } from "../../../../../tests/stubs/VerifyIsTrackerInAHierarchyStub";
import { UserIdentifierStub } from "../../../../../tests/stubs/UserIdentifierStub";
import { RetrieveUserHistoryStub } from "../../../../../tests/stubs/RetrieveUserHistoryStub";
import { okAsync } from "neverthrow";
import { SearchArtifactsStub } from "../../../../../tests/stubs/SearchArtifactsStub";
import { selectOrThrow } from "@tuleap/dom";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";
import { LinkTypesCollectionStub } from "../../../../../tests/stubs/LinkTypesCollectionStub";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { ChangeNewLinkTypeStub } from "../../../../../tests/stubs/ChangeNewLinkTypeStub";

describe(`LinkedArtifactTemplate`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (linked_artifact_presenter: LinkedArtifactPresenter): void => {
        const host = {} as HostElement;

        const updateFunction = getLinkedArtifactTemplate(linked_artifact_presenter);
        updateFunction(host, target);
    };

    it.each([
        [
            "open artifact, not marked for removal",
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withDefaults({
                    identifier: LinkedArtifactIdentifierStub.withId(123),
                    title: "A parent",
                    xref: ArtifactCrossReferenceStub.withRefAndColor("art #123", "red-wine"),
                    uri: "/url/to/artifact/123",
                    status: { value: "Open", color: "flamingo-pink" },
                    is_open: true,
                    link_type: LinkTypeStub.buildChildLinkType(),
                }),
                false
            ),
        ],
        [
            "closed artifact, marked for removal",
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withDefaults({
                    identifier: LinkedArtifactIdentifierStub.withId(234),
                    title: "A child",
                    xref: ArtifactCrossReferenceStub.withRefAndColor("art #234", "surf-green"),
                    uri: "/url/to/artifact/234",
                    status: { value: "Open", color: "flamingo-pink" },
                    is_open: false,
                    link_type: LinkTypeStub.buildUntyped(),
                }),
                true
            ),
        ],
    ])(`will render a linked artifact`, (_type_of_presenter, presenter) => {
        render(presenter);

        const row = selectOrThrow(target, "[data-test=artifact-row]");
        const link = selectOrThrow(target, "[data-test=artifact-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=artifact-xref]");
        const title = selectOrThrow(target, "[data-test=artifact-title]");
        const status = selectOrThrow(target, "[data-test=artifact-status]");
        const type = selectOrThrow(target, "[data-test=artifact-link-type]");
        const expected_type = LinkType.isUntypedLink(presenter.link_type)
            ? "is Linked to"
            : presenter.link_type.label;

        expect(link.href).toBe(presenter.uri);
        expect(xref.classList.contains(`tlp-swatch-${presenter.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(presenter.xref.ref);
        expect(title.textContent?.trim()).toBe(presenter.title);
        expect(status.textContent?.trim()).toBe(presenter.status?.value);
        expect(type.textContent?.trim()).toBe(expected_type);

        expect(row.classList.contains("link-field-table-row-muted")).toBe(!presenter.is_open);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(false);
        expect(status.classList.contains("tlp-badge-flamingo-pink")).toBe(true);
    });

    it(`will render a linked artifact with no color`, () => {
        const presenter = LinkedArtifactPresenter.fromLinkedArtifact(
            LinkedArtifactStub.withDefaults({
                identifier: LinkedArtifactIdentifierStub.withId(123),
                title: "A parent",
                xref: ArtifactCrossReferenceStub.withRefAndColor("art #123", "red-wine"),
                uri: "/url/to/artifact/123",
                status: { value: "Open", color: null },
                is_open: true,
                link_type: LinkTypeStub.buildChildLinkType(),
            }),
            false
        );
        render(presenter);

        const row = selectOrThrow(target, "[data-test=artifact-row]");
        const link = selectOrThrow(target, "[data-test=artifact-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=artifact-xref]");
        const title = selectOrThrow(target, "[data-test=artifact-title]");
        const status = selectOrThrow(target, "[data-test=artifact-status]");
        const type = selectOrThrow(target, "[data-test=artifact-link-type]");

        expect(link.href).toBe(presenter.uri);
        expect(xref.classList.contains(`tlp-swatch-${presenter.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(presenter.xref.ref);
        expect(title.textContent?.trim()).toBe(presenter.title);
        expect(status.textContent?.trim()).toBe(presenter.status?.value);
        expect(type.textContent?.trim()).toBe(presenter.link_type.label);

        expect(row.classList.contains("link-field-table-row-muted")).toBe(!presenter.is_open);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(true);
    });

    describe(`getActionButton`, () => {
        let marked_for_removal_verifier: VerifyLinkIsMarkedForRemoval;

        beforeEach(() => {
            marked_for_removal_verifier =
                VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
        });

        const getHost = (linked_artifact: LinkedArtifact): HostElement => {
            const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(72);
            const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(75);
            const parents_retriever = RetrievePossibleParentsStub.withoutParents();
            const link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
            const event_dispatcher = DispatchEventsStub.buildNoOp();

            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                marked_for_removal_verifier,
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
                ChangeNewLinkTypeStub.withCount(),
                VerifyHasParentLinkStub.withNoParentLink(),
                parents_retriever,
                link_verifier,
                VerifyIsTrackerInAHierarchyStub.withNoHierarchy(),
                event_dispatcher,
                {
                    field_id: 457,
                    label: "Artifact link",
                    type: "art_link",
                    allowed_types: [],
                },
                current_artifact_identifier,
                current_tracker_identifier,
                ArtifactCrossReferenceStub.withRef("story #72"),
                LinkTypesCollectionStub.withParentPair()
            );

            return {
                linked_artifacts_presenter: LinkedArtifactCollectionPresenter.buildLoadingState(),
                controller,
            } as unknown as HostElement;
        };

        const render = (
            host: HostElement,
            linked_artifact: LinkedArtifact,
            is_marked_for_removal: boolean
        ): void => {
            const linked_artifact_presenter = LinkedArtifactPresenter.fromLinkedArtifact(
                linked_artifact,
                is_marked_for_removal
            );
            const update = getActionButton(linked_artifact_presenter);
            update(host, target);
        };

        it(`will mark the artifact for removal`, () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            const host = getHost(linked_artifact);
            render(host, linked_artifact, false);
            const button = target.querySelector("[data-test=action-button]");

            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("An expected element has not been found in template");
            }
            button.click();

            expect(
                host.linked_artifacts_presenter.linked_artifacts.some(
                    (artifact) => artifact.is_marked_for_removal
                )
            ).toBe(true);
        });

        it(`will cancel the removal of the artifact`, () => {
            marked_for_removal_verifier =
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
            const linked_artifact = LinkedArtifactStub.withDefaults();
            const host = getHost(linked_artifact);
            render(host, linked_artifact, true);
            const button = target.querySelector("[data-test=action-button]");

            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("An expected element has not been found in template");
            }
            button.click();

            expect(
                host.linked_artifacts_presenter.linked_artifacts.some(
                    (artifact) => artifact.is_marked_for_removal
                )
            ).toBe(false);
        });
    });
});
