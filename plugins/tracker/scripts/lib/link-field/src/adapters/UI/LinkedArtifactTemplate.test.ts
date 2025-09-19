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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import type { ParentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import {
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import { LinkedArtifactIdentifierStub } from "../../../tests/stubs/links/LinkedArtifactIdentifierStub";
import type { HostElement } from "./LinkField";
import { getLinkedArtifactPresenters } from "./LinkField";
import {
    getActionButton,
    getLinkedArtifactTemplate,
    getTypeTemplate,
} from "./LinkedArtifactTemplate";
import { LinkedArtifactStub } from "../../../tests/stubs/links/LinkedArtifactStub";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { setTranslator } from "../../gettext-catalog";
import { LinkFieldController } from "../../domain/LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../tests/stubs/links/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../tests/stubs/links/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../tests/stubs/links/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../tests/stubs/links/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../tests/stubs/links/VerifyLinkIsMarkedForRemovalStub";
import type { VerifyLinkIsMarkedForRemoval } from "../../domain/links/VerifyLinkIsMarkedForRemoval";
import type { LinkedArtifact, LinkedArtifactIdentifier } from "../../domain/links/LinkedArtifact";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import { ArtifactCrossReferenceStub } from "../../../tests/stubs/ArtifactCrossReferenceStub";
import { AddNewLinkStub } from "../../../tests/stubs/links/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../tests/stubs/links/RetrieveNewLinksStub";
import { DeleteNewLinkStub } from "../../../tests/stubs/links/DeleteNewLinkStub";
import { RetrievePossibleParentsStub } from "../../../tests/stubs/RetrievePossibleParentsStub";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import { LinkTypesCollectionStub } from "../../../tests/stubs/links/LinkTypesCollectionStub";
import { ChangeNewLinkTypeStub } from "../../../tests/stubs/links/ChangeNewLinkTypeStub";
import { ChangeLinkTypeStub } from "../../../tests/stubs/links/ChangeLinkTypeStub";
import { LabeledFieldStub } from "../../../tests/stubs/LabeledFieldStub";
import type { ParentTrackerIdentifier } from "../../domain/ParentTrackerIdentifier";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";

describe(`LinkedArtifactTemplate`, () => {
    let target: ShadowRoot;
    const CURRENT_PROJECT = 1025;
    beforeEach(() => {
        setTranslator({ gettext: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (linked_artifact_presenter: LinkedArtifactPresenter): void => {
        const host = {
            current_artifact_reference: Option.fromValue(
                ArtifactCrossReferenceStub.withRef("art #136"),
            ),
            controller: {
                canMarkForRemoval: (link) => (link ? true : true),
                canChangeType: (link) => (link ? true : true),
                isLinkedArtifactInCurrentProject: (artifact) =>
                    artifact.project.id === CURRENT_PROJECT,
            } as LinkFieldController,
        } as HostElement;

        const updateFunction = getLinkedArtifactTemplate(host, linked_artifact_presenter);
        updateFunction(host, target);
    };

    function* generateLinkedArtifactPresenters(): Generator<[string, LinkedArtifactPresenter]> {
        yield [
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
                true,
                false,
            ),
        ];
        yield [
            "closed artifact, marked for removal",
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withDefaults({
                    identifier: LinkedArtifactIdentifierStub.withId(234),
                    title: "A child",
                    xref: ArtifactCrossReferenceStub.withRefAndColor("art #234", "surf-green"),
                    uri: "/url/to/artifact/234",
                    status: { value: "Open", color: "flamingo-pink" },
                    is_open: false,
                    link_type: LinkTypeStub.buildDefaultLinkType(),
                }),
                false,
                true,
            ),
        ];
        yield [
            "a parent artifact",
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withIdAndType(815, LinkTypeStub.buildChildLinkType()),
                true,
                false,
            ),
        ];
    }

    it.each([...generateLinkedArtifactPresenters()])(
        `will render a linked artifact`,
        (_type_of_presenter, presenter) => {
            render(presenter);

            const row = selectOrThrow(target, "[data-test=artifact-row]");
            const link = selectOrThrow(target, "[data-test=artifact-link]", HTMLAnchorElement);
            const xref = selectOrThrow(target, "[data-test=artifact-xref]");
            const title = selectOrThrow(target, "[data-test=artifact-title]");
            const status = selectOrThrow(target, "[data-test=artifact-status]");

            expect(link.href).toBe(presenter.uri);
            expect(xref.classList.contains(`tlp-swatch-${presenter.xref.color}`)).toBe(true);
            expect(xref.textContent?.trim()).toBe(presenter.xref.ref);
            expect(title.textContent?.trim()).toBe(presenter.title);
            expect(status.textContent?.trim()).toBe(presenter.status?.value);

            expect(row.classList.contains("link-field-parent-row")).toBe(presenter.is_parent);
            expect(row.classList.contains("link-field-row-muted")).toBe(!presenter.is_open);
            expect(status.classList.contains("tlp-badge-secondary")).toBe(false);
            expect(status.classList.contains("tlp-badge-flamingo-pink")).toBe(true);
        },
    );

    it("will render a linked artifact with project label if the artifact is not in the current project", () => {
        const presenter = LinkedArtifactPresenter.fromLinkedArtifact(
            LinkedArtifactStub.withProject({
                id: 6,
                label: "Corsa OPC",
            }),
            false,
            true,
        );
        render(presenter);

        const project = selectOrThrow(target, "[data-test=artifact-project-label]");

        expect(project).not.toBeNull();
    });

    it(`will render a linked artifact with no status color`, () => {
        const presenter = LinkedArtifactPresenter.fromLinkedArtifact(
            LinkedArtifactStub.withDefaults({
                status: { value: "Open", color: null },
            }),
            false,
            false,
        );
        render(presenter);

        const status = selectOrThrow(target, "[data-test=artifact-status]");

        expect(status.textContent?.trim()).toBe(presenter.status?.value);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(true);
    });

    describe(`controller actions`, () => {
        let marked_for_removal_verifier: VerifyLinkIsMarkedForRemoval;

        beforeEach(() => {
            marked_for_removal_verifier =
                VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
        });

        const getHost = (linked_artifact: LinkedArtifact): HostElement => {
            const current_tracker_identifier = CurrentTrackerIdentifier.fromId(75);
            const current_artifact_reference = Option.fromValue(
                ArtifactCrossReferenceStub.withRef("story #72"),
            );

            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                ChangeLinkTypeStub.withCount(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                marked_for_removal_verifier,
                AddNewLinkStub.withCount(),
                DeleteNewLinkStub.withCount(),
                RetrieveNewLinksStub.withoutLink(),
                ChangeNewLinkTypeStub.withCount(),
                RetrievePossibleParentsStub.withoutParents(),
                DispatchEventsStub.buildNoOp(),
                LabeledFieldStub.withDefaults(),
                current_tracker_identifier,
                Option.nothing<ParentTrackerIdentifier>(),
                current_artifact_reference,
                LinkTypesCollectionStub.withParentPair(),
                CurrentProjectIdentifier.fromId(101),
                Option.nothing<ParentArtifactIdentifier>(),
            );

            const linked_artifacts: ReadonlyArray<LinkedArtifact> = [];
            const linked_artifact_presenters: ReadonlyArray<LinkedArtifactPresenter> = [];
            const parent_artifacts: ReadonlyArray<LinkedArtifactIdentifier> = [];
            const host = Object.assign(target, {
                current_artifact_reference,
                linked_artifacts,
                linked_artifact_presenters,
                parent_artifact_ids: parent_artifacts,
                allowed_link_types: CollectionOfAllowedLinksTypesPresenters.buildEmpty(),
                controller,
            } as HostElement);
            return new Proxy(host, {
                set: (target, property, new_value): boolean => {
                    Reflect.set(target, property, new_value);
                    if (property === "linked_artifacts") {
                        // Simulate hybrids re-computing the property
                        target.linked_artifact_presenters = getLinkedArtifactPresenters(target);
                    }
                    return true;
                },
            });
        };

        describe(`getActionButton`, () => {
            const render = (
                host: HostElement,
                linked_artifact: LinkedArtifact,
                is_marked_for_removal: boolean,
            ): void => {
                const linked_artifact_presenter = LinkedArtifactPresenter.fromLinkedArtifact(
                    linked_artifact,
                    false,
                    is_marked_for_removal,
                );
                const update = getActionButton(host, linked_artifact_presenter);
                update(host, target);
            };

            it(`will not render a button when the link can't be deleted`, () => {
                const linked_artifact = LinkedArtifactStub.withIdAndType(
                    33,
                    LinkTypeStub.buildMirrors(),
                );
                const host = getHost(linked_artifact);
                render(host, linked_artifact, false);
                expect(target.children).toHaveLength(0);
            });

            it(`will mark the artifact for removal and dispatch a bubbling "change" event`, () => {
                const linked_artifact = LinkedArtifactStub.withDefaults();
                const host = getHost(linked_artifact);
                render(host, linked_artifact, false);
                const dispatchEvent = vi.spyOn(host, "dispatchEvent");
                const button = selectOrThrow(
                    target,
                    "[data-test=action-button]",
                    HTMLButtonElement,
                );
                button.click();

                expect(
                    host.linked_artifact_presenters.some(
                        (artifact) => artifact.is_marked_for_removal,
                    ),
                ).toBe(true);
                const event = dispatchEvent.mock.calls[0][0];
                expect(event.type).toBe("change");
                expect(event.bubbles).toBe(true);
            });

            it(`will cancel the removal of the artifact and dispatch a bubbling "change" event`, () => {
                marked_for_removal_verifier =
                    VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
                const linked_artifact = LinkedArtifactStub.withDefaults();
                const host = getHost(linked_artifact);
                render(host, linked_artifact, true);
                const dispatchEvent = vi.spyOn(host, "dispatchEvent");
                const button = selectOrThrow(
                    target,
                    "[data-test=action-button]",
                    HTMLButtonElement,
                );
                button.click();

                expect(
                    host.linked_artifact_presenters.some(
                        (artifact) => artifact.is_marked_for_removal,
                    ),
                ).toBe(false);
                const event = dispatchEvent.mock.calls[0][0];
                expect(event.type).toBe("change");
                expect(event.bubbles).toBe(true);
            });
        });

        describe(`getTypeTemplate()`, () => {
            const render = (host: HostElement, linked_artifact: LinkedArtifact): void => {
                const presenter = LinkedArtifactPresenter.fromLinkedArtifact(
                    linked_artifact,
                    false,
                    false,
                );
                const update = getTypeTemplate(host, presenter);
                update(host, target);
            };

            it(`when I can't change the type of link, it returns a readonly type label`, () => {
                const linked_artifact = LinkedArtifactStub.withIdAndType(
                    36,
                    LinkTypeStub.buildMirroredBy(),
                );

                render(getHost(linked_artifact), linked_artifact);
                expect(target.querySelector("[data-test=readonly-type]")).not.toBeNull();
            });

            it(`when I can change the type of link, it returns a type selector`, () => {
                const linked_artifact = LinkedArtifactStub.withIdAndType(
                    58,
                    LinkTypeStub.buildDefaultLinkType(),
                );

                render(getHost(linked_artifact), linked_artifact);
                expect(target.querySelector("[data-test=type-selector]")).not.toBeNull();
            });
        });
    });
});
