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
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";

const NEW_ARTIFACT_ID = 81;

describe(`AddLinkButtonTemplate`, () => {
    let host: HostElement,
        new_link_adder: AddNewLinkStub,
        link_addition_presenter: LinkAdditionPresenter,
        link_selector: LinkSelectorStub;

    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        new_link_adder = AddNewLinkStub.withCount();
        const linkable_artifact = LinkableArtifactStub.withDefaults({ id: NEW_ARTIFACT_ID });
        link_addition_presenter = LinkAdditionPresenter.withArtifactSelected(linkable_artifact);
        link_selector = LinkSelectorStub.withResetSelectionCallCount();
    });

    const render = (): HTMLButtonElement => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(62);
        const controller = LinkFieldController(
            RetrieveAllLinkedArtifactsStub.withoutLink(),
            RetrieveLinkedArtifactsSyncStub.withoutLink(),
            AddLinkMarkedForRemovalStub.withCount(),
            DeleteLinkMarkedForRemovalStub.withCount(),
            VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
            NotifyFaultStub.withCount(),
            {
                field_id: 696,
                label: "Artifact link",
                type: "art_link",
                allowed_types: [],
            },
            ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact(
                    LinkableArtifactStub.withDefaults()
                ),
                current_artifact_identifier
            ),
            new_link_adder,
            RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(NEW_ARTIFACT_ID, LinkTypeStub.buildUntyped())
            ),
            current_artifact_identifier,
            ArtifactCrossReferenceStub.withRef("story #62")
        );

        host = {
            link_addition_presenter,
            current_link_type: LinkTypeStub.buildUntyped(),
            new_links_presenter: NewLinkCollectionPresenter.buildEmpty(),
            link_selector,
            controller,
        } as unknown as HostElement;

        const updateFunction = getAddLinkButtonTemplate(host);
        updateFunction(host, target);

        const button = target.querySelector("[data-test=add-new-link-button]");
        if (!(button instanceof HTMLButtonElement)) {
            throw new Error("An expected element has not been found in template");
        }
        return button;
    };

    it(`when an artifact has been selected, clicking the button will add a new link and reset the link selector`, () => {
        const button = render();
        expect(button.disabled).toBe(false);
        button.click();

        expect(new_link_adder.getCallCount()).toBe(1);
        expect(link_selector.getCallCount()).toBe(1);
        expect(host.new_links_presenter.links).toHaveLength(1);

        const new_link = host.new_links_presenter.links[0];
        expect(new_link.identifier.id).toBe(NEW_ARTIFACT_ID);
        expect(new_link.link_type.shortname).toBe(UNTYPED_LINK);
    });

    it(`when there is no selected artifact, the button will be disabled`, () => {
        link_addition_presenter = LinkAdditionPresenter.withoutSelection();
        const button = render();
        expect(button.disabled).toBe(true);
        button.click();

        expect(new_link_adder.getCallCount()).toBe(0);
        expect(link_selector.getCallCount()).toBe(0);
    });
});
