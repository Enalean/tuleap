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
import type { NewLink } from "../../../../domain/fields/link-field-v2/NewLink";
import { LinkFieldController } from "./LinkFieldController";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import { RetrieveSelectedLinkTypeStub } from "../../../../../tests/stubs/RetrieveSelectedLinkTypeStub";
import { SetSelectedLinkTypeStub } from "../../../../../tests/stubs/SetSelectedLinkTypeStub";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";

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
                status: "On Going",
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
                status: "Delivered",
                is_open: false,
                link_type: LinkTypeStub.buildParentLinkType(),
            }),
        ],
    ])(`will render an artifact about to be linked (a new link)`, (_type_of_link, new_link) => {
        render(new_link);

        const row = target.querySelector("[data-test=link-row]");
        const link = target.querySelector("[data-test=link-link]");
        const xref = target.querySelector("[data-test=link-xref]");
        const title = target.querySelector("[data-test=link-title]");
        const status = target.querySelector("[data-test=link-status]");
        const type = target.querySelector("[data-test=link-type]");
        if (
            !(row instanceof HTMLElement) ||
            !(link instanceof HTMLAnchorElement) ||
            !(xref instanceof HTMLElement) ||
            !(title instanceof HTMLElement) ||
            !(status instanceof HTMLElement) ||
            !(type instanceof HTMLElement)
        ) {
            throw new Error("An expected element has not been found in template");
        }
        const expected_type =
            new_link.link_type.shortname === UNTYPED_LINK ? "Linked to" : new_link.link_type.label;

        expect(link.href).toBe(new_link.uri);
        expect(xref.classList.contains(`tlp-swatch-${new_link.xref.color}`)).toBe(true);
        expect(xref.textContent?.trim()).toBe(new_link.xref.ref);
        expect(title.textContent?.trim()).toBe(new_link.title);
        expect(status.textContent?.trim()).toBe(new_link.status);
        expect(type.textContent?.trim()).toBe(expected_type);

        expect(row.classList.contains("link-field-table-row-new")).toBe(true);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(!new_link.is_open);
        expect(status.classList.contains("tlp-badge-success")).toBe(new_link.is_open);
    });

    describe(`action button`, () => {
        const getHost = (new_link: NewLink): HostElement => {
            const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(22);
            const fault_notifier = NotifyFaultStub.withCount();
            const type_retriever = RetrieveSelectedLinkTypeStub.withType(
                LinkTypeStub.buildUntyped()
            );
            const notification_clearer = ClearFaultNotificationStub.withCount();
            const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(28);
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
                    type_retriever,
                    parents_retriever,
                    link_verifier,
                    current_artifact_identifier,
                    current_tracker_identifier
                ),
                AddNewLinkStub.withCount(),
                DeleteNewLinkStub.withCount(),
                RetrieveNewLinksStub.withoutLink(),
                VerifyHasParentLinkStub.withNoParentLink(),
                type_retriever,
                SetSelectedLinkTypeStub.buildPassThrough(),
                parents_retriever,
                link_verifier,
                {
                    field_id: 525,
                    label: "Artifact link",
                    type: "art_link",
                    allowed_types: [
                        {
                            shortname: IS_CHILD_LINK_TYPE,
                            forward_label: "Child",
                            reverse_label: "Parent",
                        },
                    ],
                },
                current_artifact_identifier,
                current_tracker_identifier,
                ArtifactCrossReferenceStub.withRef("bug #22")
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
            const button = target.querySelector("[data-test=action-button]");

            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("An expected element has not been found in template");
            }
            button.click();

            expect(host.new_links_presenter).toHaveLength(0);
        });
    });
});
