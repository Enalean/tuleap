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
import { LinkAdditionPresenter } from "./LinkAdditionPresenter";
import type { HostElement } from "./LinkField";
import { getAddLinkButtonTemplate } from "./AddLinkButtonTemplate";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { UNTYPED_LINK, IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import { RetrieveSelectedLinkTypeStub } from "../../../../../tests/stubs/RetrieveSelectedLinkTypeStub";
import { SetSelectedLinkTypeStub } from "../../../../../tests/stubs/SetSelectedLinkTypeStub";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";

const NEW_ARTIFACT_ID = 81;

describe(`AddLinkButtonTemplate`, () => {
    let host: HostElement,
        new_link_adder: AddNewLinkStub,
        link_addition_presenter: LinkAdditionPresenter;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        new_link_adder = AddNewLinkStub.withCount();
        const linkable_artifact = LinkableArtifactStub.withDefaults({ id: NEW_ARTIFACT_ID });
        link_addition_presenter = LinkAdditionPresenter.withArtifactSelected(linkable_artifact);
    });

    const render = (): HTMLButtonElement => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(62);
        const fault_notifier = NotifyFaultStub.withCount();
        const type_retriever = RetrieveSelectedLinkTypeStub.withType(
            LinkTypeStub.buildChildLinkType()
        );
        const notification_clearer = ClearFaultNotificationStub.withCount();
        const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(55);
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
            new_link_adder,
            DeleteNewLinkStub.withCount(),
            RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(NEW_ARTIFACT_ID, LinkTypeStub.buildUntyped())
            ),
            VerifyHasParentLinkStub.withNoParentLink(),
            type_retriever,
            SetSelectedLinkTypeStub.buildPassThrough(),
            parents_retriever,
            link_verifier,
            {
                field_id: 696,
                label: "Artifact link",
                type: "art_link",
                allowed_types: [
                    {
                        shortname: IS_CHILD_LINK_TYPE,
                        forward_label: "Parent",
                        reverse_label: "Child",
                    },
                ],
            },
            current_artifact_identifier,
            current_tracker_identifier,
            ArtifactCrossReferenceStub.withRef("story #62")
        );

        host = {
            link_addition_presenter,
            new_links_presenter: NewLinkCollectionPresenter.buildEmpty(),
            controller,
        } as HostElement;

        const updateFunction = getAddLinkButtonTemplate(host);
        updateFunction(host, target);

        const button = target.querySelector("[data-test=add-new-link-button]");
        if (!(button instanceof HTMLButtonElement)) {
            throw new Error("An expected element has not been found in template");
        }
        return button;
    };

    it(`when an artifact has been selected, clicking the button will add a new link`, () => {
        const button = render();
        expect(button.disabled).toBe(false);
        button.click();

        expect(new_link_adder.getCallCount()).toBe(1);
        expect(host.new_links_presenter).toHaveLength(1);

        const new_link = host.new_links_presenter[0];
        expect(new_link.identifier.id).toBe(NEW_ARTIFACT_ID);
        expect(new_link.link_type.shortname).toBe(UNTYPED_LINK);
    });

    it(`when there is no selected artifact, the button will be disabled`, () => {
        link_addition_presenter = LinkAdditionPresenter.withoutSelection();
        const button = render();
        expect(button.disabled).toBe(true);
        button.click();

        expect(new_link_adder.getCallCount()).toBe(0);
    });
});
