<!--
  - Copyright (c) Enalean, 2018-2019. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <tr class="document-tree-item-toggle-quicklook document-tree-item" v-bind:class="row_classes" v-bind:data-item-id="item.id">
        <td v-bind:colspan="colspan">
            <div v-bind:class="{ 'document-folder-content-title': item_is_not_being_uploaded, 'document-folder-content-quick-look-and-item-uploading': is_item_uploading_in_quicklook_mode }">
                <component
                    v-bind:is="cell_title_component_name"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <div class="tlp-dropdown tlp-table-cell-actions-button" v-if="item_is_not_being_uploaded">
                    <div class="tlp-dropdown-split-button">
                        <quick-look-button
                            class="quick-look-button"
                            v-on:displayQuickLook="$emit('displayQuickLook', item)"
                            v-bind:item="item"
                            data-test="quick-look-button"
                        />
                        <dropdown-button v-bind:is-in-quick-look-mode="true" data-test="dropdown-button">
                            <dropdown-menu-for-item-quick-look v-bind:item="item" data-test="dropdown-menu"/>
                        </dropdown-button>
                    </div>
                </div>
                <upload-progress-bar
                    v-if="is_item_uploading_in_quicklook_mode"
                    v-bind:item="item"
                    data-test="progress-bar-quick-look-pane-open"
                />
            </div>
        </td>
        <template v-if="is_item_uploading_without_quick_look_mode">
            <td><upload-progress-bar v-bind:item="item" data-test="progress-bar-quick-look-pane-closed"/></td>
            <td></td>
        </template>
        <template v-else-if="is_not_uploading_and_is_not_in_quicklook">
            <td class="document-tree-cell-owner">
                <user-badge v-bind:user="item.owner"/>
            </td>
            <td class="document-tree-cell-updatedate tlp-tooltip tlp-tooltip-left" v-bind:data-tlp-tooltip="formatted_full_date">
                {{ formatted_date }}
            </td>
        </template>
    </tr>
</template>

<script>
import { mapState } from "vuex";
import UserBadge from "../User/UserBadge.vue";
import QuickLookButton from "./QuickLook/QuickLookButton.vue";
import UploadProgressBar from "./ProgressBar/UploadProgressBar.vue";
import DropdownButton from "./Dropdown/DropdownButton.vue";
import DropdownMenuForItemQuickLook from "./Dropdown/DropdownMenuForItemQuickLook.vue";

import { TYPE_FILE, TYPE_FOLDER, TYPE_LINK, TYPE_WIKI } from "../../constants.js";
import {
    formatDateUsingPreferredUserFormat,
    getElapsedTimeFromNow
} from "../../helpers/date-formatter.js";
import { TYPE_EMBEDDED } from "../../constants";
import {
    hasNoUploadingContent,
    isItemUploadingInQuickLookMode,
    isItemUploadingInTreeView,
    isItemInTreeViewWithoutUpload
} from "../../helpers/uploading-status-helper.js";

export default {
    name: "FolderContentRow",
    components: {
        DropdownMenuForItemQuickLook,
        QuickLookButton,
        UserBadge,
        UploadProgressBar,
        DropdownButton
    },
    props: {
        item: Object,
        isQuickLookDisplayed: Boolean
    },
    computed: {
        ...mapState(["date_time_format", "folded_items_ids"]),
        formatted_date() {
            return getElapsedTimeFromNow(this.item.last_update_date);
        },
        formatted_full_date() {
            return formatDateUsingPreferredUserFormat(
                this.item.last_update_date,
                this.date_time_format
            );
        },
        is_folded() {
            return this.folded_items_ids.includes(this.item.id);
        },
        item_indentation() {
            if (!this.item.level) {
                return;
            }

            const indentation_size = this.item.level * 23;

            return {
                "padding-left": `${indentation_size}px`
            };
        },
        row_classes() {
            return {
                "document-tree-item-hidden": this.is_folded,
                "document-tree-item-created": this.item.created,
                "document-tree-item-uploading": this.item.is_uploading,
                "document-tree-item-folder": this.item.type === TYPE_FOLDER,
                "document-tree-item-file": this.item.type === TYPE_FILE
            };
        },
        cell_title_component_name() {
            let name = "Document";
            switch (this.item.type) {
                case TYPE_FILE:
                    if (this.item.is_uploading) {
                        name = "FileUploading";
                    } else {
                        name = "File";
                    }

                    break;
                case TYPE_EMBEDDED:
                case TYPE_FOLDER:
                case TYPE_LINK:
                case TYPE_WIKI:
                    name = this.item.type;
                    name = name.charAt(0).toUpperCase() + name.slice(1);
                    break;
                default:
                    break;
            }
            return () =>
                import(/* webpackChunkName: "document-cell-title-" */ `./ItemTitle/${name}CellTitle.vue`);
        },
        colspan() {
            return this.item.is_uploading ? 4 : 1;
        },
        item_is_not_being_uploaded() {
            return hasNoUploadingContent(this.item);
        },
        is_item_uploading_in_quicklook_mode() {
            return isItemUploadingInQuickLookMode(this.item, this.isQuickLookDisplayed);
        },
        is_item_uploading_without_quick_look_mode() {
            return isItemUploadingInTreeView(this.item, this.isQuickLookDisplayed);
        },
        is_not_uploading_and_is_not_in_quicklook() {
            return isItemInTreeViewWithoutUpload(this.item, this.isQuickLookDisplayed);
        }
    },
    mounted() {
        if (!(this.item.created || this.item.is_uploading)) {
            return;
        }

        const magic_number_in_px_to_detect_if_we_partially_show_the_item = 20;
        const position_from_top =
            this.$el.getBoundingClientRect().top +
            magic_number_in_px_to_detect_if_we_partially_show_the_item;
        const viewport_height = window.innerHeight || document.documentElement.clientHeight;
        const is_under_the_fold = position_from_top > viewport_height;

        if (is_under_the_fold) {
            document.dispatchEvent(
                new CustomEvent("item-has-been-created-under-the-fold", {
                    detail: { item: this.item }
                })
            );
        }
    }
};
</script>
