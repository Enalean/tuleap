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

import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import type { LinkedArtifactIdentifier } from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import type { AddLinkMarkedForRemoval } from "../../../../domain/fields/link-field-v2/AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "../../../../domain/fields/link-field-v2/DeleteLinkMarkedForRemoval";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field-v2/VerifyLinkIsMarkedForRemoval";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field-v2/RetrieveLinkedArtifactsSync";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { LinkRetrievalFault } from "../../../../domain/fields/link-field-v2/LinkRetrievalFault";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import type { ArtifactLinkSelectorAutoCompleterType } from "./ArtifactLinkSelectorAutoCompleter";
import type { LinkSelector, LinkSelectorSearchFieldCallback } from "@tuleap/link-selector";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";
import { LinkAdditionPresenter } from "./LinkAdditionPresenter";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import type { AddNewLink } from "../../../../domain/fields/link-field-v2/AddNewLink";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field-v2/RetrieveNewLinks";
import { NewLink } from "../../../../domain/fields/link-field-v2/NewLink";
import { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import type { DeleteNewLink } from "../../../../domain/fields/link-field-v2/DeleteNewLink";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import type { VerifyHasParentLink } from "../../../../domain/fields/link-field-v2/VerifyHasParentLink";
import type { RetrieveSelectedLinkType } from "../../../../domain/fields/link-field-v2/RetrieveSelectedLinkType";
import type { SetSelectedLinkType } from "../../../../domain/fields/link-field-v2/SetSelectedLinkType";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field-v2/RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";
import { PossibleParentsGroup } from "./PossibleParentsGroup";
import type { ClearFaultNotification } from "../../../../domain/ClearFaultNotification";
import type { VerifyIsAlreadyLinked } from "../../../../domain/fields/link-field-v2/VerifyIsAlreadyLinked";

export type LinkFieldPresenterAndAllowedLinkTypes = {
    readonly field: LinkFieldPresenter;
    readonly selected_link_type: LinkType;
};

export type LinkFieldControllerType = {
    displayField(): LinkFieldPresenterAndAllowedLinkTypes;
    displayLinkedArtifacts(): Promise<LinkedArtifactCollectionPresenter>;
    displayAllowedTypes(): CollectionOfAllowedLinksTypesPresenters;
    markForRemoval(artifact_id: LinkedArtifactIdentifier): LinkedArtifactCollectionPresenter;
    unmarkForRemoval(artifact_id: LinkedArtifactIdentifier): LinkedArtifactCollectionPresenter;
    autoComplete: LinkSelectorSearchFieldCallback;
    onLinkableArtifactSelection(artifact: LinkableArtifact | null): LinkAdditionPresenter;
    addNewLink(artifact: LinkableArtifact): NewLinkCollectionPresenter;
    removeNewLink(link: NewLink): NewLinkCollectionPresenter;
    setSelectedLinkType(link_selector: LinkSelector, type: LinkType): LinkType;
};

const isCreationModeFault = (fault: Fault): boolean =>
    "isCreationMode" in fault && fault.isCreationMode() === true;

const buildPresenter = (
    links_store: RetrieveLinkedArtifactsSync,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval
): LinkedArtifactCollectionPresenter => {
    const presenters = links_store
        .getLinkedArtifacts()
        .map((linked_artifact) =>
            LinkedArtifactPresenter.fromLinkedArtifact(
                linked_artifact,
                deleted_link_verifier.isMarkedForRemoval(linked_artifact)
            )
        );
    return LinkedArtifactCollectionPresenter.fromArtifacts(presenters);
};

export const LinkFieldController = (
    links_retriever: RetrieveAllLinkedArtifacts,
    links_store: RetrieveLinkedArtifactsSync,
    deleted_link_adder: AddLinkMarkedForRemoval,
    deleted_link_remover: DeleteLinkMarkedForRemoval,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
    fault_notifier: NotifyFault,
    notification_clearer: ClearFaultNotification,
    links_autocompleter: ArtifactLinkSelectorAutoCompleterType,
    new_link_adder: AddNewLink,
    new_link_remover: DeleteNewLink,
    new_links_retriever: RetrieveNewLinks,
    parent_verifier: VerifyHasParentLink,
    type_retriever: RetrieveSelectedLinkType,
    type_setter: SetSelectedLinkType,
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    field: ArtifactLinkFieldStructure,
    current_artifact_identifier: CurrentArtifactIdentifier | null,
    current_tracker_identifier: CurrentTrackerIdentifier,
    current_artifact_reference: ArtifactCrossReference | null
): LinkFieldControllerType => {
    const only_is_child_type = field.allowed_types.filter(
        (type) => type.shortname === IS_CHILD_LINK_TYPE
    );
    return {
        displayField: () => ({
            field: LinkFieldPresenter.fromFieldAndCrossReference(field, current_artifact_reference),
            selected_link_type: type_retriever.getSelectedLinkType(),
        }),

        displayAllowedTypes: () =>
            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                parent_verifier,
                only_is_child_type
            ),

        displayLinkedArtifacts: () =>
            links_retriever.getLinkedArtifacts(current_artifact_identifier).match(
                (artifacts) => {
                    const presenters = artifacts.map((linked_artifact) =>
                        LinkedArtifactPresenter.fromLinkedArtifact(linked_artifact, false)
                    );
                    return LinkedArtifactCollectionPresenter.fromArtifacts(presenters);
                },
                (fault) => {
                    if (!isCreationModeFault(fault)) {
                        fault_notifier.onFault(LinkRetrievalFault(fault));
                    }
                    return LinkedArtifactCollectionPresenter.forFault();
                }
            ),

        markForRemoval(artifact_identifier): LinkedArtifactCollectionPresenter {
            deleted_link_adder.addLinkMarkedForRemoval(artifact_identifier);
            return buildPresenter(links_store, deleted_link_verifier);
        },

        unmarkForRemoval(artifact_identifier): LinkedArtifactCollectionPresenter {
            deleted_link_remover.deleteLinkMarkedForRemoval(artifact_identifier);
            return buildPresenter(links_store, deleted_link_verifier);
        },

        autoComplete: links_autocompleter.autoComplete,

        onLinkableArtifactSelection: (artifact): LinkAdditionPresenter => {
            if (!artifact) {
                return LinkAdditionPresenter.withoutSelection();
            }
            return LinkAdditionPresenter.withArtifactSelected(artifact);
        },

        addNewLink(artifact): NewLinkCollectionPresenter {
            const previous_link_type = type_retriever.getSelectedLinkType();
            new_link_adder.addNewLink(
                NewLink.fromLinkableArtifactAndType(artifact, previous_link_type)
            );
            return NewLinkCollectionPresenter.fromLinks(new_links_retriever.getNewLinks());
        },

        removeNewLink(link): NewLinkCollectionPresenter {
            new_link_remover.deleteNewLink(link);
            return NewLinkCollectionPresenter.fromLinks(new_links_retriever.getNewLinks());
        },

        setSelectedLinkType: (link_selector, type): LinkType => {
            if (!LinkType.isReverseChild(type)) {
                return type_setter.setSelectedLinkType(type);
            }

            notification_clearer.clearFaultNotification();
            link_selector.setDropdownContent([PossibleParentsGroup.buildLoadingState()]);
            parents_retriever.getPossibleParents(current_tracker_identifier).match(
                (possible_parents) => {
                    link_selector.setDropdownContent([
                        PossibleParentsGroup.fromPossibleParents(link_verifier, possible_parents),
                    ]);
                },
                (fault) => {
                    fault_notifier.onFault(fault);
                    link_selector.setDropdownContent([PossibleParentsGroup.buildEmpty()]);
                }
            );
            return type_setter.setSelectedLinkType(type);
        },
    };
};
