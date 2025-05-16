/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { getFolderSubtree } from "../helpers/retrieve-subtree-helper";
import { isFolder } from "../helpers/type-check-helper";

export {
    saveFolderContent,
    addJustCreatedItemToFolderContent,
    appendSubFolderContent,
    foldFolderContent,
    unfoldFolderContent,
    resetFoldedLists,
    removeCreatedPropertyOnItem,
    replaceUploadingFileWithActualFile,
    removeItemFromFolderContent,
    addDocumentToFoldedFolder,
    updateCurrentItemForQuickLokDisplay,
};

function saveFolderContent(state, folder_content) {
    state.folder_content = folder_content;
}

function addDocumentToTheRightPlace(state, new_item, parent) {
    const near_sibling_index = state.folder_content.findIndex(
        (sibling) =>
            !isFolder(sibling) &&
            sibling.parent_id === new_item.parent_id &&
            sibling.title.localeCompare(new_item.title, undefined, {
                numeric: true,
            }) >= 0,
    );

    const has_no_sibling_and_no_parent = near_sibling_index === -1 && !parent;
    const has_a_parent_but_no_siblings = near_sibling_index === -1 && parent;

    if (has_no_sibling_and_no_parent) {
        state.folder_content.push(new_item);

        return;
    } else if (has_a_parent_but_no_siblings) {
        const document_siblings = state.folder_content.filter(
            (item) => item.parent_id === new_item.parent_id,
        );

        let nearest_sibling;

        if (!document_siblings.length) {
            nearest_sibling = parent;
        } else {
            nearest_sibling = document_siblings[document_siblings.length - 1];
        }

        const nearest_sibling_index = state.folder_content.findIndex(
            (item) => item.id === nearest_sibling.id,
        );

        state.folder_content.splice(nearest_sibling_index + 1, 0, new_item);

        return;
    }

    state.folder_content.splice(near_sibling_index, 0, new_item);
}

function addFolderToTheRightPlace(state, new_item, parent) {
    const folder_siblings = state.folder_content.filter(
        (item) => isFolder(item) && item.parent_id === new_item.parent_id,
    );

    let nearest_sibling = folder_siblings.find((sibling) => {
        return (
            sibling.title.localeCompare(new_item.title, undefined, {
                numeric: true,
            }) >= 0
        );
    });

    const is_the_last_of_its_siblings = !nearest_sibling && folder_siblings.length > 0;

    if (is_the_last_of_its_siblings) {
        nearest_sibling = folder_siblings[folder_siblings.length - 1];

        const nearest_sibling_index = state.folder_content.findIndex(
            (item) => item.id === nearest_sibling.id,
        );

        state.folder_content.splice(nearest_sibling_index + 1, 0, new_item);
    } else if (nearest_sibling) {
        const nearest_sibling_index = state.folder_content.findIndex(
            (item) => item.id === nearest_sibling.id,
        );

        state.folder_content.splice(nearest_sibling_index, 0, new_item);
    } else {
        if (parent) {
            const parent_index = state.folder_content.findIndex((item) => item.id === parent.id);

            state.folder_content.splice(parent_index + 1, 0, new_item);
        } else {
            state.folder_content.splice(0, 0, new_item);
        }
    }
}

function addJustCreatedItemToFolderContent(state, new_item) {
    const parent = state.folder_content.find((parent) => parent.id === new_item.parent_id);

    if (parent && !parent.level) {
        parent.level = 0;
    }

    new_item.level = parent ? parent.level + 1 : 0;

    if (!isFolder(new_item)) {
        return addDocumentToTheRightPlace(state, new_item, parent);
    }

    return addFolderToTheRightPlace(state, new_item, parent);
}

function appendSubFolderContent(state, [folder_id, sub_items]) {
    const folder_index = state.folder_content.findIndex((folder) => folder.id === folder_id);
    const parent_folder = state.folder_content[folder_index];
    if (!parent_folder) {
        return;
    }

    if (!parent_folder.level) {
        parent_folder.level = 0;
    }

    sub_items.forEach((item) => {
        item.level = parent_folder.level + 1;
    });

    const filtered_sub_items = sub_items.filter(
        (item) =>
            state.folder_content.findIndex((existing_item) => item.id === existing_item.id) === -1,
    );

    state.folder_content.splice(folder_index + 1, 0, ...filtered_sub_items);

    const children_ids = filtered_sub_items.map((item) => item.id);

    if (isParentFoldedByOnOfIsAncestors(state, parent_folder)) {
        state.folded_items_ids.push(...children_ids);

        const folder = findAncestorFoldingFolder(state, folder_id);

        if (folder) {
            state.folded_by_map[folder[0]].push(...children_ids);
        }
    }
}

function findAncestorFoldingFolder(state, folder_id) {
    return Object.entries(state.folded_by_map).find(([folder_key]) => {
        return state.folded_by_map[folder_key].includes(folder_id);
    });
}

function isParentFoldedByOnOfIsAncestors(state, parent_folder) {
    return state.folded_items_ids.find((folded_item_id) => folded_item_id === parent_folder.id);
}

function foldFolderContent(state, folder_id) {
    const index = state.folder_content.findIndex((item) => item.id === folder_id);

    if (index !== -1) {
        state.folder_content[index].is_expanded = false;
    }
    const children = getFolderUnfoldedDescendants(state, folder_id);
    const folded_content = children.map((item) => item.id);

    state.folded_items_ids = state.folded_items_ids.concat(folded_content);

    state.folded_by_map[folder_id] = folded_content;
}

function addDocumentToFoldedFolder(state, [parent, item, should_display_fake_item]) {
    if (!should_display_fake_item) {
        if (!state.folded_by_map[parent.id]) {
            state.folded_by_map[parent.id] = [];
        }

        state.folded_by_map[parent.id].push(item.id);
        state.folded_items_ids.push(item.id);
    }
}

function unfoldFolderContent(state, folder_id) {
    const index = state.folder_content.findIndex((item) => item.id === folder_id);

    if (index !== -1) {
        state.folder_content[index].is_expanded = true;
    }

    const items_to_unfold = state.folded_by_map[folder_id];

    if (!items_to_unfold) {
        return;
    }

    state.folded_items_ids = state.folded_items_ids.filter(
        (item) => !items_to_unfold.includes(item),
    );

    delete state.folded_by_map[folder_id];
}

function resetFoldedLists(state) {
    state.folded_items_ids = [];
    state.folded_by_map = {};
}

function getFolderUnfoldedDescendants(state, folder_id) {
    const children = state.folder_content.filter((item) => item.parent_id === folder_id);

    const unfolded_descendants = [];

    children.forEach((child) => {
        if (Object.prototype.hasOwnProperty.call(state.folded_by_map, child.id)) {
            return;
        }

        unfolded_descendants.push(...getFolderUnfoldedDescendants(state, child.id));
    });

    return children.concat(unfolded_descendants);
}

function removeCreatedPropertyOnItem(state, item) {
    delete item["created"];
}

function replaceUploadingFileWithActualFile(state, [uploading_file, actual_file]) {
    const index = state.folder_content.findIndex((item) => item.id === uploading_file.id);
    if (index === -1) {
        return;
    }

    state.folder_content.splice(index, 1, actual_file);
}

function removeItemFromFolderContent(state, item_to_remove) {
    const index = state.folder_content.findIndex((item) => item.id === item_to_remove.id);
    if (index === -1) {
        return;
    }

    if (isFolder(item_to_remove)) {
        unfoldFolderContent(state, item_to_remove.id);

        const children = getFolderSubtree(state.folder_content, item_to_remove.id);

        children.forEach((child) => {
            if (isFolder(child)) {
                unfoldFolderContent(state, child.id);
            }

            removeItemFromFolderContent(state, child);
        });
    }

    state.folder_content.splice(index, 1);
}

function updateCurrentItemForQuickLokDisplay(state, item) {
    state.currently_previewed_item = item;
}
