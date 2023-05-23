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
    getString: (source: string) => string;
}

let gettextCatalog: GettextCatalog;

export function setCatalog(catalog: GettextCatalog): void {
    gettextCatalog = catalog;
}

export const getTextLabel = (): string => gettextCatalog.getString("Text");
export const getHTMLLabel = (): string => gettextCatalog.getString("HTML");
export const getCommonMarkLabel = (): string => gettextCatalog.getString("Markdown");
export const getCommentLabel = (): string => gettextCatalog.getString("Comment");
export const getSyntaxHelperTitle = (): string => gettextCatalog.getString("Help");
export const getSyntaxHelperType = (): string => gettextCatalog.getString("Type...");
export const getSyntaxHelperToGet = (): string => gettextCatalog.getString("...to get");
export const getRTEHelpMessage = (): string =>
    gettextCatalog.getString("You can drag 'n drop or paste image directly in the editor.");
export const getUploadSizeExceeded = (): string =>
    gettextCatalog.getString("You are not allowed to upload files bigger than %s.");
export const getUploadError = (): string => gettextCatalog.getString("Unable to upload the file");
export const getNoPasteMessage = (): string =>
    gettextCatalog.getString("You are not allowed to paste images here");
export const getCommonMarkSyntaxHelperPopoverTitle = (): string =>
    gettextCatalog.getString("For your information...");
export const getEditButtonLabel = (): string => gettextCatalog.getString("Edit");
export const getPreviewButtonLabel = (): string => gettextCatalog.getString("Preview");
export const getCommonMarkPreviewErrorIntroduction = (): string =>
    gettextCatalog.getString("There was an error in the Markdown preview: ");
export const getNone = (): string => gettextCatalog.getString("None");
export const getAutocomputeLabel = (): string => gettextCatalog.getString("Auto-compute");
export const getAutoComputedValueLabel = (): string => gettextCatalog.getString("(auto-computed)");
export const getComputedValueLabel = (): string => gettextCatalog.getString("Computed value:");
export const getEmptyLabel = (): string => gettextCatalog.getString("Empty");
export const getLinkFieldFetchErrorMessage = (): string =>
    gettextCatalog.getString("Unable to retrieve the linked artifacts: %s");
export const getLinkedParentFeedback = (): string =>
    gettextCatalog.getString("The artifact will be linked to %s");
export const getLinkFieldTableEmptyStateText = (): string => gettextCatalog.getString("No links");
export const getLinkFieldNoteText = (): string =>
    gettextCatalog.getString("Note: an artifact can only have one parent.");
export const getLinkFieldNoteStartText = (): string => gettextCatalog.getString(`Note:`);
export const getLinkFieldCanHaveOnlyOneParent = (): string =>
    gettextCatalog.getString(`can only have one parent.`);
export const getMarkForRemovalLabel = (): string => gettextCatalog.getString("Mark for removal");
export const getUnlinkLabel = (): string => gettextCatalog.getString("Unlink");
export const getRestoreLabel = (): string => gettextCatalog.getString("Restore");
export const getFieldDateRequiredAndEmptyMessage = (): string =>
    gettextCatalog.getString("Please select a date");
export const getEmptyCrossReferencesCollectionText = (): string =>
    gettextCatalog.getString("References list is empty.");
export const getDefaultLinkTypeLabel = (): string => gettextCatalog.getString("is Linked to");
export const getChildTypeLabel = (): string => gettextCatalog.getString("is Child of");
export const getParentTypeLabel = (): string => gettextCatalog.getString("is Parent of");
export const getMaxAllowedUploadSizeText = (): string =>
    gettextCatalog.getString("Max allowed upload size: %s");
export const getFileSubmittedByText = (): string => gettextCatalog.getString("By: %s");
export const getFileSizeText = (): string => gettextCatalog.getString("Size: %s");
export const getUndoFileRemovalLabel = (): string => gettextCatalog.getString("Keep the file");
export const getFileDescriptionPlaceholder = (): string =>
    gettextCatalog.getString("File description");
export const getResetLabel = (): string => gettextCatalog.getString("Reset");
export const getAddFileButtonLabel = (): string => gettextCatalog.getString("Add another file");
export const getNewArtifactLabel = (): string => gettextCatalog.getString("New artifact");
export const getParentFetchErrorMessage = (): string =>
    gettextCatalog.getString("Unable to retrieve the parent artifact: %s");
export const getMatchingArtifactLabel = (): string => gettextCatalog.getString("Matching artifact");
export const getNoResultFoundEmptyState = (): string => gettextCatalog.getString("No result found");
export const getLinkSelectorPlaceholderText = (): string =>
    gettextCatalog.getString("Search for an artifact...");
export const getLinkSelectorSearchPlaceholderText = (): string =>
    gettextCatalog.getString("Id, title...");
export const getMatchingArtifactErrorMessage = (): string =>
    gettextCatalog.getString("Error while retrieving the artifact to link: %s");
export const getRemoveLabel = (): string => gettextCatalog.getString("Remove");
export const getPermissionFieldLabel = (): string =>
    gettextCatalog.getString("Restrict access to this artifact for the following user groups:");
export const getPossibleParentsLabel = (): string => gettextCatalog.getString("Possible parents");
export const getPossibleParentsEmptyState = (): string =>
    gettextCatalog.getString("No possible parent found");
export const getPossibleParentErrorMessage = (): string =>
    gettextCatalog.getString("Error while retrieving the possible parents: %s");
export const getAlreadyLinkedTextTooltip = (): string =>
    gettextCatalog.getString("This artifact is already linked");
export const getAlreadyLinkedInfo = (): string => gettextCatalog.getString("(already linked)");
export const getFileUploadErrorMessage = (): string =>
    gettextCatalog.getString("Error while uploading %(file_name)s: %(error)s");
export const getRecentlyViewedArtifactGroupLabel = (): string =>
    gettextCatalog.getString("Recently viewed artifacts");
export const getSearchResultsGroupLabel = (): string => gettextCatalog.getString("Search results");
export const getSearchResultsGroupFootMessage = (): string =>
    gettextCatalog.getString(
        "Please refine your search if you did not find what you are looking for."
    );
export const getUserHistoryErrorMessage = (): string =>
    gettextCatalog.getString("Error while retrieving recently viewed artifacts: %s");
export const getSearchArtifactsErrorMessage = (): string =>
    gettextCatalog.getString("Error while searching for artifacts: %s");
export const getCommentsSectionTitle = (): string => gettextCatalog.getString("Follow-ups");
export const getChangesetsCommentMessage = (): string =>
    gettextCatalog.getString("Only comments are displayed");
export const getEmptyCommentsMessage = (): string => gettextCatalog.getString("No follow-ups");
export const getFollowupEditedBy = (): string => gettextCatalog.getString("Edited by %(user)s");
export const getCommentsRetrievalErrorMessage = (): string =>
    gettextCatalog.getString("Error while retrieving the comments: %s");
export const getSubmitDisabledForLinksReason = (): string =>
    gettextCatalog.getString("Linked artifacts are loading");
export const getSubmitDisabledImageUploadReason = (): string =>
    gettextCatalog.getString("An image in a text field or in a new comment is being uploaded");
export const getCreateNewArtifactButtonInLinkLabel = (): string =>
    gettextCatalog.getString("→ Create new artifact…");
export const getCreateArtifactButtonInCreatorLabel = (): string =>
    gettextCatalog.getString("Create");
export const getCancelArtifactCreationLabel = (): string => gettextCatalog.getString("Cancel");
export const getArtifactCreationInputPlaceholderText = (): string =>
    gettextCatalog.getString("Title");
export const getSubmitDisabledForProjectsAndTrackersReason = (): string =>
    gettextCatalog.getString("Projects and trackers are loading");
export const getArtifactCreationProjectLabel = (): string => gettextCatalog.getString("Project");
export const getArtifactCreationFeedbackErrorMessage = (): string =>
    gettextCatalog.getString("Something went wrong during the artifact creation.");
export const getArtifactFeedbackShowMoreLabel = (): string =>
    gettextCatalog.getString("View details");
export const getProjectsRetrievalErrorMessage = (): string =>
    gettextCatalog.getString("Error while retrieving the list of projects: %s");
