/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type {
    CommonEvents,
    CurrentArtifactIdentifier,
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    DispatchEvents,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import type { AllowedLinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import type { LocaleString } from "@tuleap/gettext";
import { initGettextSync } from "@tuleap/gettext";
import { ArtifactCreatorController } from "./domain/creation/ArtifactCreatorController";
import type { ArtifactLinkSelectorAutoCompleterType } from "./adapters/UI/dropdown/ArtifactLinkSelectorAutoCompleter";
import { ArtifactLinkSelectorAutoCompleter } from "./adapters/UI/dropdown/ArtifactLinkSelectorAutoCompleter";
import { LinkFieldController } from "./domain/LinkFieldController";
import type { LabeledField } from "./domain/LabeledField";
import type { ArtifactCrossReference } from "./domain/ArtifactCrossReference";
import type { ParentTrackerIdentifier } from "./domain/ParentTrackerIdentifier";
import type { UserIdentifier } from "./domain/UserIdentifier";
import { LinkFieldAPIClient } from "./adapters/REST/LinkFieldAPIClient";
import { ArtifactCreationAPIClient } from "./adapters/REST/creation/ArtifactCreationAPIClient";
import type { LinksStore } from "./adapters/Memory/LinksStore";
import type { NewLinksStore } from "./adapters/Memory/NewLinksStore";
import type { LinksMarkedForRemovalStore } from "./adapters/Memory/LinksMarkedForRemovalStore";
import { LinksRetriever } from "./domain/links/LinksRetriever";
import { PossibleParentsCache } from "./adapters/Memory/PossibleParentsCache";
import { LinkTypesCollector } from "./adapters/REST/LinkTypesCollector";
import { AlreadyLinkedVerifier } from "./domain/links/AlreadyLinkedVerifier";
import { UserHistoryCache } from "./adapters/Memory/UserHistoryCache";
import { ProjectsCache } from "./adapters/Memory/ProjectsCache";
import { LinkableArtifactCreator } from "./adapters/REST/creation/LinkableArtifactCreator";
import { setTranslator } from "./gettext-catalog";
import * as fr_FR from "../po/fr_FR.po";

export interface LinkFieldCreator {
    createLinkFieldController(
        field: LabeledField,
        allowed_link_types: ReadonlyArray<AllowedLinkTypeRepresentation>,
    ): LinkFieldController;
    createLinkSelectorAutoCompleter(): ArtifactLinkSelectorAutoCompleterType;
    createArtifactCreatorController(): ArtifactCreatorController;
}

export const LinkFieldCreator = (
    event_dispatcher: DispatchEvents<CommonEvents>,
    links_store: LinksStore,
    new_links_store: NewLinksStore,
    links_marked_for_removal_store: LinksMarkedForRemovalStore,
    current_artifact_identifier: Option<CurrentArtifactIdentifier>,
    current_artifact_reference: Option<ArtifactCrossReference>,
    current_project_identifier: CurrentProjectIdentifier,
    current_tracker_identifier: CurrentTrackerIdentifier,
    parent_artifact_identifier: Option<ParentArtifactIdentifier>,
    parent_tracker_identifier: Option<ParentTrackerIdentifier>,
    user_identifier: UserIdentifier,
    user_locale: LocaleString,
): LinkFieldCreator => {
    const gettext_provider = initGettextSync(
        "tuleap-plugin-tracker-link-field",
        fr_FR,
        user_locale,
    );
    setTranslator(gettext_provider);

    const link_field_api_client = LinkFieldAPIClient(current_artifact_identifier);
    const artifact_creation_api_client = ArtifactCreationAPIClient();

    return {
        createLinkFieldController(field, allowed_link_types): LinkFieldController {
            return LinkFieldController(
                LinksRetriever(
                    link_field_api_client,
                    link_field_api_client,
                    links_store,
                    current_artifact_identifier,
                ),
                links_store,
                links_store,
                links_marked_for_removal_store,
                links_marked_for_removal_store,
                links_marked_for_removal_store,
                new_links_store,
                new_links_store,
                new_links_store,
                new_links_store,
                PossibleParentsCache(link_field_api_client),
                event_dispatcher,
                field,
                current_tracker_identifier,
                parent_tracker_identifier,
                current_artifact_reference,
                LinkTypesCollector.buildFromTypesRepresentations(allowed_link_types),
                current_project_identifier,
                parent_artifact_identifier,
            );
        },

        createLinkSelectorAutoCompleter(): ArtifactLinkSelectorAutoCompleterType {
            return ArtifactLinkSelectorAutoCompleter(
                link_field_api_client,
                AlreadyLinkedVerifier(links_store, new_links_store),
                UserHistoryCache(link_field_api_client),
                link_field_api_client,
                event_dispatcher,
                current_artifact_identifier,
                user_identifier,
            );
        },

        createArtifactCreatorController(): ArtifactCreatorController {
            return ArtifactCreatorController(
                event_dispatcher,
                ProjectsCache(artifact_creation_api_client),
                artifact_creation_api_client,
                LinkableArtifactCreator(
                    artifact_creation_api_client,
                    artifact_creation_api_client,
                    link_field_api_client,
                ),
                current_project_identifier,
                current_tracker_identifier,
                user_locale,
            );
        },
    };
};
