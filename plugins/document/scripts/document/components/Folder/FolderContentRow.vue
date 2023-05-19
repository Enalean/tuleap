<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <tr
        class="document-tree-item-toggle-quicklook document-tree-item"
        v-bind:class="row_classes"
        v-bind:data-item-id="item.id"
        v-on:click="toggleQuickLookOnRow"
    >
        <td v-bind:colspan="colspan" v-bind:id="`document-folder-content-row-${item.id}`">
            <div
                v-bind:class="{
                    'document-folder-content-title': item_is_not_being_uploaded,
                    'document-folder-content-quick-look-and-item-uploading':
                        is_item_uploading_in_quicklook_mode,
                }"
                v-bind:id="`document-folder-content-row-div-${item.id}`"
                data-test="document-folder-content-row"
            >
                <component
                    v-bind:is="cell_title_component_name"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <div class="document-tree-item-toggle-quicklook-spacer"></div>
                <div
                    class="tlp-dropdown tlp-table-cell-actions-button"
                    v-if="item_is_not_being_uploaded"
                >
                    <div class="tlp-dropdown-split-button">
                        <quick-look-button
                            class="quick-look-button"
                            data-test="quick-look-button"
                            v-bind:item="item"
                        />
                        <drop-down-button
                            v-bind:is-in-quick-look-mode="true"
                            v-bind:is-in-large-mode="false"
                            v-bind:is-appended="true"
                            data-test="dropdown-button"
                        >
                            <drop-down-menu-tree-view
                                v-bind:item="item"
                                data-test="dropdown-menu"
                            />
                        </drop-down-button>
                    </div>
                </div>
                <upload-progress-bar
                    v-if="is_item_uploading_in_quicklook_mode"
                    v-bind:item="item"
                    data-test="progress-bar-quick-look-pane-open"
                />
                <document-title-lock-info
                    v-bind:item="item"
                    v-bind:is-displaying-in-header="false"
                />
                <approval-badge v-bind:item="item" v-bind:is-in-folder-content-row="true" />
            </div>
        </td>
        <template v-if="is_item_uploading_without_quick_look_mode">
            <td>
                <upload-progress-bar
                    v-bind:item="item"
                    data-test="progress-bar-quick-look-pane-closed"
                />
            </td>
            <td></td>
        </template>
        <template v-else-if="is_not_uploading_and_is_not_in_quicklook">
            <td class="document-tree-cell-owner">
                <user-badge v-bind:user="item.owner" />
            </td>
            <td class="document-tree-cell-updatedate">
                <document-relative-date v-bind:date="item.last_update_date" />
            </td>
        </template>
    </tr>
</template>

<script>
import { mapState } from "vuex";
import { TYPE_FILE, TYPE_FOLDER, TYPE_LINK, TYPE_WIKI, TYPE_EMBEDDED } from "../../constants";
import {
    hasNoUploadingContent,
    isItemUploadingInQuickLookMode,
    isItemUploadingInTreeView,
    isItemInTreeViewWithoutUpload,
} from "../../helpers/uploading-status-helper";
import UserBadge from "../User/UserBadge.vue";
import QuickLookButton from "./ActionsQuickLookButton/QuickLookButton.vue";
import UploadProgressBar from "./ProgressBar/UploadProgressBar.vue";
import DropDownButton from "./DropDown/DropDownButton.vue";
import DocumentTitleLockInfo from "./LockInfo/DocumentTitleLockInfo.vue";
import ApprovalBadge from "./ApprovalTables/ApprovalBadge.vue";
import DropDownMenuTreeView from "./DropDown/DropDownMenuTreeView.vue";
import { isFile, isFolder } from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";
import { defineAsyncComponent } from "vue";

export default {
    name: "FolderContentRow",
    components: {
        DocumentRelativeDate,
        DropDownMenuTreeView,
        ApprovalBadge,
        DocumentTitleLockInfo,
        QuickLookButton,
        UserBadge,
        UploadProgressBar,
        DropDownButton,
    },
    props: {
        item: Object,
        isQuickLookDisplayed: Boolean,
    },
    data() {
        return { is_dropdown_displayed: false };
    },
    computed: {
        ...mapState(["folded_items_ids"]),
        is_folded() {
            return this.folded_items_ids.includes(this.item.id);
        },
        item_indentation() {
            if (!this.item.level) {
                return {};
            }

            const indentation_size = this.item.level * 23;

            return {
                "padding-left": `${indentation_size}px`,
            };
        },
        row_classes() {
            return {
                "document-tree-item-hidden": this.is_folded,
                "document-tree-item-created": this.item.created,
                "document-tree-item-updated": this.item.updated,
                "document-tree-item-uploading": this.item.is_uploading,
                "document-tree-item-folder": isFolder(this.item),
                "document-tree-item-file": isFile(this.item),
            };
        },
        cell_title_component_name() {
            switch (this.item.type) {
                case TYPE_FILE:
                    if (this.item.is_uploading) {
                        return defineAsyncComponent(() =>
                            import(
                                /* webpackChunkName: "document-cell-title-file-uploading" */ `./ItemTitle/FileUploadingCellTitle.vue`
                            )
                        );
                    }
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-file" */ `./ItemTitle/FileCellTitle.vue`
                        )
                    );
                case TYPE_EMBEDDED:
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-embedded" */ `./ItemTitle/EmbeddedCellTitle.vue`
                        )
                    );
                case TYPE_FOLDER:
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-folder" */ `./ItemTitle/FolderCellTitle.vue`
                        )
                    );
                case TYPE_LINK:
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-link" */ `./ItemTitle/LinkCellTitle.vue`
                        )
                    );
                case TYPE_WIKI:
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-wiki" */ `./ItemTitle/WikiCellTitle.vue`
                        )
                    );
                default:
                    return defineAsyncComponent(() =>
                        import(
                            /* webpackChunkName: "document-cell-title-document" */ `./ItemTitle/DocumentCellTitle.vue`
                        )
                    );
            }
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
        },
    },
    mounted() {
        emitter.on("set-dropdown-shown", this.setIsDropdownDisplayed);
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
            emitter.emit("item-has-been-created-under-the-fold", {
                detail: { item: this.item },
            });
        }
    },
    methods: {
        toggleQuickLookOnRow(event) {
            if (
                !this.is_dropdown_displayed &&
                (event.target.id === `document-folder-content-row-${this.item.id}` ||
                    event.target.id === `document-folder-content-row-div-${this.item.id}`)
            ) {
                emitter.emit("toggle-quick-look", { details: { item: this.item } });
            }
        },
        setIsDropdownDisplayed(event) {
            this.is_dropdown_displayed = event.is_dropdown_shown;
        },
    },
};
</script>
