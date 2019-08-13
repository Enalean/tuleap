/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import "./store/actions-helpers/adjust-item-to-content-after-item-creation-in-folder.spec.js";
import "./store/actions-helpers/build-parent-paths.spec.js";

import "./store/getters.spec.js";
import "./store/mutations.spec.js";

import "./helpers/metadata-helpers/check-item-title.spec.js";
import "./helpers/metadata-helpers/data-transformatter-helper.spec.js";

import "./store/error/error-mutations.spec.js";
import "./store/error/error-getters.spec.js";

import "./store/clipboard/clipboard-mutations.spec.js";
import "./store/clipboard/clipboard-actions.spec.js";

import "./store/metadata/metadata-mutations.spec.js";
import "./store/metadata/metadata-actions.spec.js";

import "./store/store-persistence/storage.spec.js";

import "./components/Breadcrumb/DocumentBreadcrumb.spec.js";

import "./components/Folder/ApprovalTables/ApprovalTableBadge.spec.js";

import "./components/Folder/Error/GoBackToRootButton.spec.js";
import "./components/Folder/Error/ShowErrorDetails.spec.js";

import "./components/Folder/Clipboard/ClipboardContentInformation.spec.js";

import "./components/Folder/FolderContentRow.spec.js";
import "./components/Folder/FolderHeaderAction.spec.js";
import "./components/Folder/FolderContent.spec.js";

import "./components/Folder/DragNDrop/CurrentFolderDropZone.spec.js";
import "./components/Folder/DragNDrop/DragNDropHandler.spec.js";

import "./components/Folder/ActionsButton/NewItemVersionButton.spec.js";
import "./components/Folder/ActionsButton/DetailsItemButton.spec.js";
import "./components/Folder/ActionsButton/NewItemButton.spec.js";

import "./components/Folder/DropDown/CopyItem.spec.js";
import "./components/Folder/DropDown/CutItem.spec.js";
import "./components/Folder/DropDown/PasteItem.spec.js";
import "./components/Folder/DropDown/DeleteItem.spec.js";
import "./components/Folder/DropDown/DropDownButton.spec.js";
import "./components/Folder/DropDown/DropDownCurrentFolder.spec.js";
import "./components/Folder/DropDown/DropDownMenu.spec.js";
import "./components/Folder/DropDown/DropDownMenuTreeView.spec.js";
import "./components/Folder/DropDown/DropDownDisplayedEmbedded.spec.js";
import "./components/Folder/DropDown/DropDownQuickLook.spec.js";
import "./components/Folder/DropDown/NewFolderSecondaryAction.spec.js";
import "./components/Folder/DropDown/NewIDocument.spec.js";
import "./components/Folder/DropDown/UpdatePermissions.spec";
import "./components/Folder/DropDown/UpdateProperties.spec.js";
import "./components/Folder/DropDown/LockItem.spec.js";
import "./components/Folder/DropDown/UnlockItem.spec.js";

import "./components/Folder/ActionsQuickLookButton/QuickLookDeleteButton.spec.js";
import "./components/Folder/ActionsQuickLookButton/QuickLookButton.spec.js";

import "./components/Folder/ItemDisplay/ActionsHeader.spec.js";
import "./components/Folder/ItemDisplay/DisplayEmbedded.spec.js";
import "./components/Folder/ItemDisplay/EmbeddedFileEditionSwitcher.spec.js";
import "./components/Folder/ItemDisplay/DisplayEmbeddedContent.spec.js";

import "./components/Folder/ItemTitle/FakeCaret.spec.js";
import "./components/Folder/ItemTitle/FolderCellTitle.spec.js";
import "./components/Folder/ItemTitle/EmbeddedCellTitle.spec.js";
import "./components/Folder/ItemTitle/FileCellTitle.spec.js";
import "./components/Folder/ItemTitle/LinkCellTitle.spec.js";
import "./components/Folder/LockInfo/DocumentTitleLockInfo.spec.js";

import "./components/Folder/ModalDeleteItem/ModalConfirmDeletion.spec.js";
import "./components/Folder/ModalDeleteItem/AdditionalCheckboxes/DeleteAssociatedWikiPageCheckbox.spec.js";

import "./components/Folder/QuickLook/QuickLookDocumentPreview.spec.js";
import "./components/Folder/QuickLook/QuickLookDocumentAdditionalMetadataList.spec.js";
import "./components/Folder/QuickLook/QuickLookDocumentMetadata.spec.js";
import "./components/Folder/QuickLook/QuickLookGlobal.spec.js";
import "./components/Folder/QuickLook/QuickLookMetadataDate.spec.js";

import "./components/Folder/ProgressBar/UploadProgressBar.spec.js";

import "./components/Folder/ModalCommon/ModalFooter.spec.js";

import "./components/Folder/Property/ApprovalUpdateProperties.spec.js";
import "./components/Folder/Property/ItemUpdateProperties.spec.js";
import "./components/Folder/Property/LockProperty.spec.js";

import "./components/Folder/Metadata/CustomMetadata/CustomMetadata.spec.js";
import "./components/Folder/Metadata/CustomMetadata/CustomMetadataText.spec.js";
import "./components/Folder/Metadata/CustomMetadata/CustomMetadataString.spec.js";
import "./components/Folder/Metadata/CustomMetadata/CustomMetadataListSingleValue.spec.js";
import "./components/Folder/Metadata/CustomMetadata/CustomMetadataListMultipleValue.spec.js";

import "./components/Folder/Metadata/TitleMetadata.spec.js";
import "./components/Folder/Metadata/ObsolescenceMetadata/ObsolescenceDateMetadataForCreate.spec.js";
import "./components/Folder/Metadata/ObsolescenceMetadata/ObsolescenceDateMetadataForUpdate.spec.js";
import "./components/Folder/Metadata/DocumentMetadata/OtherInformationMetadataForUpdate.spec.js";
import "./components/Folder/Metadata/DocumentMetadata/OtherInformationMetadataForCreate.spec.js";
import "./components/Folder/Metadata/OwnerMetadata.spec.js";

import "./components/Folder/Metadata/DocumentMetadata/StatusMetadataWithCustomBindingForDocumentCreate.spec.js";
import "./components/Folder/Metadata/DocumentMetadata/StatusMetadataWithCustomBindingForDocumentUpdate.spec.js";

import "./components/Folder/Metadata/FolderMetadata/StatusMetadataWithCustomBindingForFolderCreate.spec.js";
import "./components/Folder/Metadata/FolderMetadata/StatusMetadataWithCustomBindingForFolderUpdate.spec.js";
import "./components/Folder/Metadata/FolderMetadata/FolderDefaultPropertiesForUpdate.spec.js";
import "./components/Folder/Metadata/FolderMetadata/FolderDefaultPropertiesForCreate.spec.js";

import "./components/Folder/ModalNewItem/NewItemModal.spec.js";

import "./components/Folder/Permissions/PermissionsUpdateModal.spec.js";
import "./components/Folder/Permissions/PermissionsSelector.spec.js";

import "./components/User/UserBadge.spec.js";
import "./components/User/UserName.spec.js";

import "./helpers/highlight-items-helper.spec.js";
import "./helpers/uploading-status-helper.spec.js";
import "./helpers/approval-table-helper.spec.js";
import "./helpers/clipboard/clipboard-helpers.spec.js";
import "./helpers/metadata-helpers/hardcoded-metadata-mapping-helper.spec.js";
import "./helpers/metadata-helpers/obsolescence-date-value.spec.js";
import "./helpers/permissions/ugroups.spec.js";
import "./helpers/metadata-helpers/custom-metadata-helper.spec.js";
