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
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field/RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import type { LinkedArtifactIdentifier } from "../../../../domain/fields/link-field/LinkedArtifact";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import type { AddLinkMarkedForRemoval } from "../../../../domain/fields/link-field/AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "../../../../domain/fields/link-field/DeleteLinkMarkedForRemoval";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsSync";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { LinkRetrievalFault } from "../../../../domain/fields/link-field/LinkRetrievalFault";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import type { ArtifactLinkSelectorAutoCompleterType } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import type { GroupOfItems } from "@tuleap/link-selector";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import type { AddNewLink } from "../../../../domain/fields/link-field/AddNewLink";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field/RetrieveNewLinks";
import { NewLink } from "../../../../domain/fields/link-field/NewLink";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type { DeleteNewLink } from "../../../../domain/fields/link-field/DeleteNewLink";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { VerifyHasParentLink } from "../../../../domain/fields/link-field/VerifyHasParentLink";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field/RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";
import { PossibleParentsGroup } from "./dropdown/PossibleParentsGroup";
import type { ClearFaultNotification } from "../../../../domain/ClearFaultNotification";
import type { VerifyIsAlreadyLinked } from "../../../../domain/fields/link-field/VerifyIsAlreadyLinked";
import type {
    ControlLinkedArtifactsPopovers,
    LinkedArtifactPopoverElement,
} from "./LinkedArtifactsPopoversController";
import type { LinkField } from "./LinkField";
import type { CollectAllowedLinksTypes } from "../../../../domain/fields/link-field/CollectAllowedLinksTypes";
import type { VerifyIsTrackerInAHierarchy } from "../../../../domain/fields/link-field/VerifyIsTrackerInAHierarchy";
import type { DispatchEvents } from "../../../../domain/DispatchEvents";
import { WillDisableSubmit } from "../../../../domain/submit/WillDisableSubmit";
import { WillEnableSubmit } from "../../../../domain/submit/WillEnableSubmit";
import { getSubmitDisabledForLinksReason } from "../../../../gettext-catalog";

export type LinkFieldControllerType = {
    displayField(): LinkFieldPresenter;
    displayLinkedArtifacts(): PromiseLike<LinkedArtifactCollectionPresenter>;
    displayAllowedTypes(): CollectionOfAllowedLinksTypesPresenters;
    markForRemoval(artifact_id: LinkedArtifactIdentifier): LinkedArtifactCollectionPresenter;
    unmarkForRemoval(artifact_id: LinkedArtifactIdentifier): LinkedArtifactCollectionPresenter;
    autoComplete(host: LinkField, query: string): void;
    addNewLink(artifact: LinkableArtifact, type: LinkType): NewLinkCollectionPresenter;
    removeNewLink(link: NewLink): NewLinkCollectionPresenter;
    initPopovers: (popover_elements: LinkedArtifactPopoverElement[]) => void;
    retrievePossibleParentsGroups(): PromiseLike<GroupOfItems>;
    getCurrentLinkType(has_possible_parents: boolean): LinkType;
    clearFaultNotification(): void;
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
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    tracker_hierarchy_verifier: VerifyIsTrackerInAHierarchy,
    event_dispatcher: DispatchEvents,
    control_popovers: ControlLinkedArtifactsPopovers,
    field: ArtifactLinkFieldStructure,
    current_artifact_identifier: CurrentArtifactIdentifier | null,
    current_tracker_identifier: CurrentTrackerIdentifier,
    current_artifact_reference: ArtifactCrossReference | null,
    allowed_links_types_collection: CollectAllowedLinksTypes
): LinkFieldControllerType => ({
    displayField: () =>
        LinkFieldPresenter.fromFieldAndCrossReference(field, current_artifact_reference),

    getCurrentLinkType: (has_possible_parents: boolean): LinkType => {
        const reverse_child_type = allowed_links_types_collection.getReverseChildType();
        return reverse_child_type &&
            !parent_verifier.hasParentLink() &&
            (tracker_hierarchy_verifier.isTrackerInAHierarchy() || has_possible_parents)
            ? reverse_child_type
            : LinkType.buildUntyped();
    },

    displayAllowedTypes: () =>
        CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
            parent_verifier,
            allowed_links_types_collection
        ),

    displayLinkedArtifacts: (): PromiseLike<LinkedArtifactCollectionPresenter> => {
        event_dispatcher.dispatch(WillDisableSubmit(getSubmitDisabledForLinksReason()));
        return links_retriever.getLinkedArtifacts(current_artifact_identifier).match(
            (artifacts) => {
                event_dispatcher.dispatch(WillEnableSubmit());
                const presenters = artifacts.map((linked_artifact) =>
                    LinkedArtifactPresenter.fromLinkedArtifact(linked_artifact, false)
                );
                return LinkedArtifactCollectionPresenter.fromArtifacts(presenters);
            },
            (fault) => {
                if (isCreationModeFault(fault)) {
                    event_dispatcher.dispatch(WillEnableSubmit());
                } else {
                    fault_notifier.onFault(LinkRetrievalFault(fault));
                }
                return LinkedArtifactCollectionPresenter.forFault();
            }
        );
    },

    markForRemoval(artifact_identifier): LinkedArtifactCollectionPresenter {
        deleted_link_adder.addLinkMarkedForRemoval(artifact_identifier);
        return buildPresenter(links_store, deleted_link_verifier);
    },

    unmarkForRemoval(artifact_identifier): LinkedArtifactCollectionPresenter {
        deleted_link_remover.deleteLinkMarkedForRemoval(artifact_identifier);
        return buildPresenter(links_store, deleted_link_verifier);
    },

    autoComplete: links_autocompleter.autoComplete,

    clearFaultNotification: notification_clearer.clearFaultNotification,

    addNewLink(artifact, type): NewLinkCollectionPresenter {
        new_link_adder.addNewLink(NewLink.fromLinkableArtifactAndType(artifact, type));
        return NewLinkCollectionPresenter.fromLinks(new_links_retriever.getNewLinks());
    },

    removeNewLink(link): NewLinkCollectionPresenter {
        new_link_remover.deleteNewLink(link);
        return NewLinkCollectionPresenter.fromLinks(new_links_retriever.getNewLinks());
    },

    retrievePossibleParentsGroups(): PromiseLike<GroupOfItems> {
        notification_clearer.clearFaultNotification();
        return parents_retriever.getPossibleParents(current_tracker_identifier).match(
            (possible_parents) =>
                PossibleParentsGroup.fromPossibleParents(link_verifier, possible_parents),
            (fault) => {
                fault_notifier.onFault(fault);
                return PossibleParentsGroup.buildEmpty();
            }
        );
    },

    initPopovers(popover_elements: LinkedArtifactPopoverElement[]): void {
        control_popovers.initPopovers(popover_elements);
    },
});
