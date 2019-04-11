/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import "tlp-mocks";

import "./api/rest-querier.spec.js";
import "./store/actions.spec.js";
import "./store/actions-helpers/load-ascendant-hierarchy.spec.js";
import "./store/actions-helpers/load-folder-content.spec.js";
import "./store/getters.spec.js";
import "./store/mutations.spec.js";

import "./store/error/error-mutations.spec.js";

import "./components/Breadcrumb/DocumentBreadCrumb.spec.js";

import "./components/Folder/Error/GoBackToRootButton.spec.js";
import "./components/Folder/Error/ShowErrorDetails.spec.js";

import "./components/Folder/FolderContentRow.spec.js";

import "./components/Folder/DragNDrop/CurrentFolderDropZone.spec.js";
import "./components/Folder/DragNDrop/DragNDropHandler.spec.js";

import "./components/Folder/ActionsButton/UpdateButton.spec.js";

import "./components/Folder/ActionsDropDown/DropdownButton.spec.js";
import "./components/Folder/ActionsDropDown/DropdownMenu.spec.js";
import "./components/Folder/ActionsDropDown/DropdownMenuForItemQuickLook.spec.js";

import "./components/Folder/ActionsQuickLookButton/QuickLookDeleteButton.spec.js";

import "./components/Folder/ItemDisplay/DisplayEmbedded.spec.js";

import "./components/Folder/ItemTitle/FakeCaret.spec.js";
import "./components/Folder/ItemTitle/FolderCellTitle.spec.js";
import "./components/Folder/ItemTitle/EmbeddedCellTitle.spec.js";
import "./components/Folder/ItemTitle/FileCellTitle.spec.js";
import "./components/Folder/ItemTitle/LinkCellTitle.spec.js";

import "./components/Folder/QuickLook/QuickLookDocumentPreview.spec.js";

import "./components/Folder/ModalCommon/ModalFooter.spec.js";

import "./components/Folder/ModalUpdateItem/ApprovalUpdateProperties.spec.js";
import "./components/Folder/ModalUpdateItem/ItemUpdateProperties.spec.js";

import "./helpers/highlight-items-helper.spec.js";
import "./helpers/uploading-status-helper.spec.js";
