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

let gettextCatalog = null;

export function setCatalog(catalog) {
    gettextCatalog = catalog;
}

export const getTextLabel = () => gettextCatalog.getString("Text");
export const getHTMLLabel = () => gettextCatalog.getString("HTML");
export const getCommonMarkLabel = () => gettextCatalog.getString("Markdown");
export const getCommentLabel = () => gettextCatalog.getString("Comment");
export const getSyntaxHelperTitle = () => gettextCatalog.getString("Help");
export const getSyntaxHelperType = () => gettextCatalog.getString("Type...");
export const getSyntaxHelperToGet = () => gettextCatalog.getString("...to get");
export const getRTEHelpMessage = () =>
    gettextCatalog.getString("You can drag 'n drop or paste image directly in the editor.");
export const getUploadSizeExceeded = () =>
    gettextCatalog.getString("You are not allowed to upload files bigger than %s.");
export const getUploadError = () => gettextCatalog.getString("Unable to upload the file");
export const getNoPasteMessage = () =>
    gettextCatalog.getString("You are not allowed to paste images here");
export const getCommonMarkSyntaxHelperPopoverTitle = () =>
    gettextCatalog.getString("For your information...");
export const getEditButtonLabel = () => gettextCatalog.getString("Edit");
export const getPreviewButtonLabel = () => gettextCatalog.getString("Preview");
export const getCommonMarkPreviewErrorIntroduction = () =>
    gettextCatalog.getString("There was an error in the Markdown preview: ");
export const getNone = () => gettextCatalog.getString("None");
