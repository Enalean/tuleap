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
export const getLinkFieldUnderConstructionPlaceholder = (): string =>
    gettextCatalog.getString("Field under implementation, please come back later");
export const getLinkFieldFetchErrorMessage = (): string =>
    gettextCatalog.getString("Unable to retrieve the linked artifacts: %s");
