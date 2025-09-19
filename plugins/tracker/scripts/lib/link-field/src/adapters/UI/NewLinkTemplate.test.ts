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

import { beforeEach, describe, expect, it } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import type { ParentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import {
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import { setTranslator } from "../../gettext-catalog";
import type { HostElement } from "./LinkField";
import { getNewLinkTemplate } from "./NewLinkTemplate";
import { NewLinkStub } from "../../../tests/stubs/links/NewLinkStub";
import { ArtifactCrossReferenceStub } from "../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import type { NewLink } from "../../domain/links/NewLink";
import { LinkFieldController } from "../../domain/LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../tests/stubs/links/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../tests/stubs/links/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../tests/stubs/links/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../tests/stubs/links/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../tests/stubs/links/VerifyLinkIsMarkedForRemovalStub";
import { AddNewLinkStub } from "../../../tests/stubs/links/AddNewLinkStub";
import { DeleteNewLinkStub } from "../../../tests/stubs/links/DeleteNewLinkStub";
import { RetrieveNewLinksStub } from "../../../tests/stubs/links/RetrieveNewLinksStub";
import { RetrievePossibleParentsStub } from "../../../tests/stubs/RetrievePossibleParentsStub";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import { LinkTypesCollectionStub } from "../../../tests/stubs/links/LinkTypesCollectionStub";
import { ChangeNewLinkTypeStub } from "../../../tests/stubs/links/ChangeNewLinkTypeStub";
import { ChangeLinkTypeStub } from "../../../tests/stubs/links/ChangeLinkTypeStub";
import { LabeledFieldStub } from "../../../tests/stubs/LabeledFieldStub";
import type { ParentTrackerIdentifier } from "../../domain/ParentTrackerIdentifier";
import { ProjectStub } from "../../../tests/stubs/ProjectStub";

describe(`NewLinkTemplate`, () => {
    let target: ShadowRoot;
    const CURRENT_PROJECT = 1025;

    beforeEach(() => {
        setTranslator({ gettext: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (link: NewLink): void => {
        const host = {
            current_artifact_reference: Option.fromValue(
                ArtifactCrossReferenceStub.withRef("story #330"),
            ),
            controller: {
                isLinkedArtifactInCurrentProject: (artifact) =>
                    artifact.project.id === CURRENT_PROJECT,
            },
        } as HostElement;

        const updateFunction = getNewLinkTemplate(host, link);
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
                link_type: LinkTypeStub.buildDefaultLinkType(),
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
                link_type: LinkTypeStub.buildChildLinkType(),
            }),
        ],
    ])(`will render an artifact about to be linked (a new link)`, (_type_of_link, new_link) => {
        render(new_link);

        const row = selectOrThrow(target, "[data-test=link-row]");
        const link = selectOrThrow(target, "[data-test=link-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=link-xref]");
        const title = selectOrThrow(target, "[data-test=link-title]");
        const status = selectOrThrow(target, "[data-test=link-status]");

        expect(link.href).toBe(new_link.uri);
        expect(xref.classList.contains(`tlp-swatch-${new_link.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(new_link.xref.ref);
        expect(title.textContent?.trim()).toBe(new_link.title);
        expect(status.textContent?.trim()).toBe(new_link.status?.value);

        expect(row.classList.contains("link-field-new-row")).toBe(true);
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
            link_type: LinkTypeStub.buildChildLinkType(),
        });
        render(new_link);

        const row = selectOrThrow(target, "[data-test=link-row]");
        const link = selectOrThrow(target, "[data-test=link-link]", HTMLAnchorElement);
        const xref = selectOrThrow(target, "[data-test=link-xref]");
        const title = selectOrThrow(target, "[data-test=link-title]");
        const status = selectOrThrow(target, "[data-test=link-status]");

        expect(link.href).toBe(new_link.uri);
        expect(xref.classList.contains(`tlp-swatch-${new_link.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(new_link.xref.ref);
        expect(title.textContent?.trim()).toBe(new_link.title);
        expect(status.textContent?.trim()).toBe(new_link.status?.value);

        expect(row.classList.contains("link-field-new-row")).toBe(true);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(true);
    });

    it("will render a linked artifact with project label if the artifact is not in the current project", () => {
        const new_link = NewLinkStub.withDefaults(246, {
            project: ProjectStub.withDefaults({ id: 15, label: "Corsa GSi" }),
        });
        render(new_link);

        const project = selectOrThrow(target, "[data-test=artifact-project-label]");

        expect(project).not.toBeNull();
    });

    describe(`action button`, () => {
        const getHost = (new_link: NewLink): HostElement => {
            const current_tracker_identifier = CurrentTrackerIdentifier.fromId(28);
            const current_artifact_reference = Option.fromValue(
                ArtifactCrossReferenceStub.withRef("bug #22"),
            );

            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withoutLink(),
                ChangeLinkTypeStub.withCount(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
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

            const new_links: ReadonlyArray<NewLink> = [new_link];
            return {
                current_artifact_reference,
                new_links,
                controller,
            } as HostElement;
        };

        const render = (host: HostElement, new_link: NewLink): void => {
            const update = getNewLinkTemplate(host, new_link);
            update(host, target);
        };

        it(`will delete the new link`, () => {
            const new_link = NewLinkStub.withDefaults();
            const host = getHost(new_link);
            render(host, new_link);
            const button = selectOrThrow(target, "[data-test=action-button]", HTMLButtonElement);
            button.click();

            expect(host.new_links).toHaveLength(0);
        });
    });
});
