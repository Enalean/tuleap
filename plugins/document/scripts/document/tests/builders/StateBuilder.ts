/**
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
import type { FolderContentItem, State } from "../../src/type";

export class StateBuilder {
    private is_loading_folder = false;
    private toggle_quick_look = false;
    private folder_content: Array<FolderContentItem> = [];
    private folded_by_map: { [key: number]: Array<number> } = {};
    private folded_items_ids: Array<number> = [];

    public thatIsLoadingFolder(is_loading_folder: boolean): this {
        this.is_loading_folder = is_loading_folder;
        return this;
    }

    public withToggleQuickLook(toggle_quick_look: boolean): this {
        this.toggle_quick_look = toggle_quick_look;
        return this;
    }

    public withFoldedByMap(folded_by_map: { [key: number]: Array<number> }): this {
        this.folded_by_map = folded_by_map;
        return this;
    }

    public withFoldedItemsIds(folded_items_ids: Array<number>): this {
        this.folded_items_ids = folded_items_ids;
        return this;
    }

    public withFolderContent(folder_content: Array<FolderContentItem>): this {
        this.folder_content = folder_content;
        return this;
    }

    public build(): State {
        return {
            current_folder: null,
            current_folder_ascendant_hierarchy: [],
            currently_previewed_item: null,
            files_uploads_list: [],
            folded_by_map: this.folded_by_map,
            folded_items_ids: this.folded_items_ids,
            folder_content: this.folder_content,
            is_loading_ascendant_hierarchy: false,
            is_loading_currently_previewed_item: false,
            is_loading_folder: this.is_loading_folder,
            project_ugroups: [],
            root_title: "",
            show_post_deletion_notification: false,
            toggle_quick_look: this.toggle_quick_look,
        };
    }
}
