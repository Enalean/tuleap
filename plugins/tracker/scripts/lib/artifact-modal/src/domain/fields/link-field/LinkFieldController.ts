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

import type { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";
import type { RetrieveAllLinkedArtifacts } from "./RetrieveAllLinkedArtifacts";
import type { LinkedArtifact, LinkedArtifactIdentifier } from "./LinkedArtifact";
import type { AddLinkMarkedForRemoval } from "./AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "./DeleteLinkMarkedForRemoval";
import type { VerifyLinkIsMarkedForRemoval } from "./VerifyLinkIsMarkedForRemoval";
import type { RetrieveLinkedArtifactsSync } from "./RetrieveLinkedArtifactsSync";
import { LinkRetrievalFault } from "./LinkRetrievalFault";
import type { LabeledField } from "../Field";
import type { ArtifactCrossReference } from "../../ArtifactCrossReference";
import type { LinkableArtifact } from "./LinkableArtifact";
import type { AddNewLink } from "./AddNewLink";
import type { RetrieveNewLinks } from "./RetrieveNewLinks";
import { NewLink } from "./NewLink";
import { LinkType } from "./LinkType";
import type { DeleteNewLink } from "./DeleteNewLink";
import type { VerifyHasParentLink } from "./VerifyHasParentLink";
import type { RetrievePossibleParents } from "./RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../CurrentTrackerIdentifier";
import type { VerifyIsAlreadyLinked } from "./VerifyIsAlreadyLinked";
import type { LinkTypesCollection } from "./LinkTypesCollection";
import type { DispatchEvents } from "../../DispatchEvents";
import { WillDisableSubmit } from "../../submit/WillDisableSubmit";
import { WillEnableSubmit } from "../../submit/WillEnableSubmit";
import { WillClearFaultNotification } from "../../WillClearFaultNotification";
import { WillNotifyFault } from "../../WillNotifyFault";
import type { ChangeNewLinkType } from "./ChangeNewLinkType";
import type { ChangeLinkType } from "./ChangeLinkType";
import type { ParentTrackerIdentifier } from "./ParentTrackerIdentifier";
import type { CurrentProjectIdentifier } from "../../CurrentProjectIdentifier";

export type LinkFieldController = {
    getCurrentArtifactReference(): Option<ArtifactCrossReference>;
    getLabeledField(): LabeledField;
    getLinkedArtifacts(disable_submit_message: string): PromiseLike<readonly LinkedArtifact[]>;
    getAllowedLinkTypes(): LinkTypesCollection;
    canMarkForRemoval(link: LinkedArtifact): boolean;
    isMarkedForRemoval(artifact: LinkedArtifact): boolean;
    markForRemoval(artifact_id: LinkedArtifactIdentifier): ReadonlyArray<LinkedArtifact>;
    unmarkForRemoval(artifact_id: LinkedArtifactIdentifier): ReadonlyArray<LinkedArtifact>;
    canChangeType(link: LinkedArtifact): boolean;
    changeLinkType(link: LinkedArtifact, new_link_type: LinkType): ReadonlyArray<LinkedArtifact>;
    addNewLink(artifact: LinkableArtifact, type: LinkType): ReadonlyArray<NewLink>;
    removeNewLink(link: NewLink): ReadonlyArray<NewLink>;
    changeNewLinkType(link: NewLink, new_link_type: LinkType): ReadonlyArray<NewLink>;
    getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>>;
    hasParentLink(): boolean;
    getCurrentLinkType(has_possible_parents: boolean): LinkType;
    clearFaultNotification(): void;
    isLinkedArtifactInCurrentProject(artifact: LinkedArtifact): boolean;
};

const isCreationModeFault = (fault: Fault): boolean =>
    "isCreationMode" in fault && fault.isCreationMode() === true;

export const LinkFieldController = (
    links_retriever: RetrieveAllLinkedArtifacts,
    links_store: RetrieveLinkedArtifactsSync,
    link_type_changer: ChangeLinkType,
    deleted_link_adder: AddLinkMarkedForRemoval,
    deleted_link_remover: DeleteLinkMarkedForRemoval,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
    new_link_adder: AddNewLink,
    new_link_remover: DeleteNewLink,
    new_links_retriever: RetrieveNewLinks,
    new_link_type_changer: ChangeNewLinkType,
    parent_verifier: VerifyHasParentLink,
    parents_retriever: RetrievePossibleParents,
    link_verifier: VerifyIsAlreadyLinked,
    event_dispatcher: DispatchEvents,
    field: LabeledField,
    current_tracker_identifier: CurrentTrackerIdentifier,
    parent_tracker_identifier: Option<ParentTrackerIdentifier>,
    current_artifact_reference: Option<ArtifactCrossReference>,
    allowed_link_types_collection: LinkTypesCollection,
    current_project_identifier: CurrentProjectIdentifier
): LinkFieldController => ({
    getCurrentArtifactReference: () => current_artifact_reference,

    getLabeledField: () => field,

    getCurrentLinkType: (has_possible_parents: boolean): LinkType => {
        const reverse_child_type = allowed_link_types_collection.getReverseChildType();
        const is_tracker_in_a_hierarchy = parent_tracker_identifier.isValue();
        return reverse_child_type &&
            !parent_verifier.hasParentLink() &&
            (is_tracker_in_a_hierarchy || has_possible_parents)
            ? reverse_child_type
            : LinkType.buildUntyped();
    },

    getAllowedLinkTypes: () => allowed_link_types_collection,

    getLinkedArtifacts(disable_submit_message): PromiseLike<readonly LinkedArtifact[]> {
        event_dispatcher.dispatch(WillDisableSubmit(disable_submit_message));
        return links_retriever.getLinkedArtifacts().match(
            (artifacts) => {
                event_dispatcher.dispatch(WillEnableSubmit());
                return artifacts;
            },
            (fault) => {
                if (isCreationModeFault(fault)) {
                    event_dispatcher.dispatch(WillEnableSubmit());
                } else {
                    event_dispatcher.dispatch(WillNotifyFault(LinkRetrievalFault(fault)));
                }
                return [];
            }
        );
    },

    canMarkForRemoval(link): boolean {
        return !LinkType.isMirroredMilestone(link.link_type);
    },

    isMarkedForRemoval(link): boolean {
        return deleted_link_verifier.isMarkedForRemoval(link);
    },

    markForRemoval(artifact_identifier): ReadonlyArray<LinkedArtifact> {
        deleted_link_adder.addLinkMarkedForRemoval(artifact_identifier);
        return links_store.getLinkedArtifacts();
    },

    unmarkForRemoval(artifact_identifier): ReadonlyArray<LinkedArtifact> {
        deleted_link_remover.deleteLinkMarkedForRemoval(artifact_identifier);
        return links_store.getLinkedArtifacts();
    },

    canChangeType(link): boolean {
        return !LinkType.isMirroredMilestone(link.link_type);
    },

    changeLinkType(link, type): ReadonlyArray<LinkedArtifact> {
        link_type_changer.changeLinkType(link, type);
        return links_store.getLinkedArtifacts();
    },

    clearFaultNotification(): void {
        event_dispatcher.dispatch(WillClearFaultNotification());
    },

    addNewLink(artifact, type): ReadonlyArray<NewLink> {
        new_link_adder.addNewLink(NewLink.fromLinkableArtifactAndType(artifact, type));
        return new_links_retriever.getNewLinks();
    },

    removeNewLink(link): ReadonlyArray<NewLink> {
        new_link_remover.deleteNewLink(link);
        return new_links_retriever.getNewLinks();
    },

    changeNewLinkType(link, type): ReadonlyArray<NewLink> {
        new_link_type_changer.changeNewLinkType(link, type);
        return new_links_retriever.getNewLinks();
    },

    getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>> {
        return parents_retriever.getPossibleParents(current_tracker_identifier).match(
            (possible_parents) => possible_parents,
            (fault) => {
                event_dispatcher.dispatch(WillNotifyFault(fault));
                return [];
            }
        );
    },

    hasParentLink(): boolean {
        return parent_verifier.hasParentLink();
    },

    isLinkedArtifactInCurrentProject(artifact): boolean {
        return artifact.project.id === current_project_identifier.id;
    },
});
