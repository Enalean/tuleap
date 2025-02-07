/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

interface GettextCatalog {
    gettext: (source: string) => string;
}

let gettext_provider: GettextCatalog;

export function setTranslator(provider: GettextCatalog): void {
    gettext_provider = provider;
}

export const getLinkFieldFetchErrorMessage = (): string =>
    gettext_provider.gettext("Unable to retrieve the linked artifacts: %s");
export const getLinkFieldTableEmptyStateText = (): string => gettext_provider.gettext("No links");
export const getLinkFieldCanHaveOnlyOneParent = (): string =>
    gettext_provider.gettext("An artifact can only have one parent.");
export const getLinkFieldCanHaveOnlyOneParentWithCrossRef = (): string =>
    gettext_provider.gettext(`%(artifact)s can only have one parent.`);
export const getLinkFieldTypeAlreadySet = (): string => {
    return gettext_provider.gettext("%(type)s (already set)");
};
export const getUnlinkLabel = (): string => gettext_provider.gettext("Unlink");
export const getRestoreLabel = (): string => gettext_provider.gettext("Restore");
export const getDefaultLinkTypeLabel = (): string => gettext_provider.gettext("is Linked to");
export const getChildTypeLabel = (): string => gettext_provider.gettext("is Child of");
export const getParentTypeLabel = (): string => gettext_provider.gettext("is Parent of");

export const getNewArtifactLabel = (): string => gettext_provider.gettext("New artifact");
export const getMatchingArtifactLabel = (): string => gettext_provider.gettext("Matching artifact");
export const getNoResultFoundEmptyState = (): string => gettext_provider.gettext("No result found");
export const getLinkSelectorPlaceholderText = (): string =>
    gettext_provider.gettext("Search for an artifact...");
export const getLinkSelectorSearchPlaceholderText = (): string =>
    gettext_provider.gettext("Id, title...");
export const getMatchingArtifactErrorMessage = (): string =>
    gettext_provider.gettext("Error while retrieving the artifact to link: %s");
export const getRemoveLabel = (): string => gettext_provider.gettext("Remove");
export const getPossibleParentsLabel = (): string => gettext_provider.gettext("Possible parents");
export const getPossibleParentsEmptyState = (): string =>
    gettext_provider.gettext("No possible parent found");
export const getPossibleParentErrorMessage = (): string =>
    gettext_provider.gettext("Error while retrieving the possible parents: %s");
export const getAlreadyLinkedTextTooltip = (): string =>
    gettext_provider.gettext("This artifact is already linked");
export const getAlreadyLinkedInfo = (): string => gettext_provider.gettext("(already linked)");
export const getRecentlyViewedArtifactGroupLabel = (): string =>
    gettext_provider.gettext("Recently viewed artifacts");
export const getSearchResultsGroupLabel = (): string => gettext_provider.gettext("Search results");
export const getSearchResultsGroupFootMessage = (): string =>
    gettext_provider.gettext(
        "Please refine your search if you did not find what you are looking for.",
    );
export const getUserHistoryErrorMessage = (): string =>
    gettext_provider.gettext("Error while retrieving recently viewed artifacts: %s");
export const getSearchArtifactsErrorMessage = (): string =>
    gettext_provider.gettext("Error while searching for artifacts: %s");
export const getSubmitDisabledForLinksReason = (): string =>
    gettext_provider.gettext("Linked artifacts are loading");
export const getCreateNewArtifactButtonInLinkLabel = (): string =>
    gettext_provider.gettext("→ Create new artifact…");
export const getCreateNewArtifactButtonInLinkWithNameLabel = (): string =>
    gettext_provider.gettext(`→ Create new artifact "%(title)s"…`);
export const getCreateArtifactButtonInCreatorLabel = (): string =>
    gettext_provider.gettext("Create");
export const getCancelArtifactCreationLabel = (): string => gettext_provider.gettext("Cancel");
export const getArtifactCreationInputPlaceholderText = (): string =>
    gettext_provider.gettext("Title");
export const getSubmitDisabledForProjectsAndTrackersReason = (): string =>
    gettext_provider.gettext("Projects and trackers are loading");
export const getArtifactCreationProjectLabel = (): string => gettext_provider.gettext("Project");
export const getArtifactCreationFeedbackErrorMessage = (): string =>
    gettext_provider.gettext("Something went wrong during the artifact creation.");
export const getArtifactFeedbackShowMoreLabel = (): string =>
    gettext_provider.gettext("View details");
export const getProjectsRetrievalErrorMessage = (): string =>
    gettext_provider.gettext("Error while retrieving the list of projects: %s");
export const getArtifactCreationErrorMessage = (): string =>
    gettext_provider.gettext("Error while creating the new artifact: %s");
export const getArtifactCreationTrackerLabel = (): string => gettext_provider.gettext("Trackers");
export const getProjectTrackersRetrievalErrorMessage = (): string =>
    gettext_provider.gettext("Error while retrieving the list of trackers: %s");
export const getProjectTrackersListPickerPlaceholder = (): string =>
    gettext_provider.gettext("Select the tracker");
export const getSubmitDisabledForLinkableArtifactCreationReason = (): string =>
    gettext_provider.gettext("New artifact to link is being created");
