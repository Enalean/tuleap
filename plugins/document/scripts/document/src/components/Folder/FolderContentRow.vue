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
        ref="table_row"
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
                <template v-if="isFile(item)">
                    <file-uploading-cell-title
                        v-if="item.is_uploading"
                        v-bind:item="item"
                        v-bind:style="item_indentation"
                        v-bind:title="item.title"
                    />
                    <file-cell-title
                        v-else
                        v-bind:item="item"
                        v-bind:style="item_indentation"
                        v-bind:title="item.title"
                    />
                </template>
                <embedded-cell-title
                    v-else-if="isEmbedded(item)"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <folder-cell-title
                    v-else-if="isFolder(item)"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <link-cell-title
                    v-else-if="isLink(item)"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <wiki-cell-title
                    v-else-if="isWiki(item)"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <empty-document-cell-title
                    v-else-if="isEmpty(item)"
                    v-bind:item="item"
                    v-bind:style="item_indentation"
                    v-bind:title="item.title"
                />
                <other-document-cell-title
                    v-else
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

<script setup lang="ts">
import {
    hasNoUploadingContent,
    isItemInTreeViewWithoutUpload,
    isItemUploadingInQuickLookMode,
    isItemUploadingInTreeView,
} from "../../helpers/uploading-status-helper";
import UserBadge from "../User/UserBadge.vue";
import QuickLookButton from "./ActionsQuickLookButton/QuickLookButton.vue";
import UploadProgressBar from "./ProgressBar/UploadProgressBar.vue";
import DropDownButton from "./DropDown/DropDownButton.vue";
import DocumentTitleLockInfo from "./LockInfo/DocumentTitleLockInfo.vue";
import ApprovalBadge from "./ApprovalTables/ApprovalBadge.vue";
import DropDownMenuTreeView from "./DropDown/DropDownMenuTreeView.vue";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isWiki,
} from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";
import { computed, defineAsyncComponent, onMounted, ref } from "vue";
import type { FolderContentItem, RootState } from "../../type";
import { useState } from "vuex-composition-helpers";

const FileUploadingCellTitle = defineAsyncComponent(
    () => import("./ItemTitle/FileUploadingCellTitle.vue"),
);
const FileCellTitle = defineAsyncComponent(() => import("./ItemTitle/FileCellTitle.vue"));
const EmbeddedCellTitle = defineAsyncComponent(() => import("./ItemTitle/EmbeddedCellTitle.vue"));
const FolderCellTitle = defineAsyncComponent(() => import("./ItemTitle/FolderCellTitle.vue"));
const LinkCellTitle = defineAsyncComponent(() => import("./ItemTitle/LinkCellTitle.vue"));
const WikiCellTitle = defineAsyncComponent(() => import("./ItemTitle/WikiCellTitle.vue"));
const EmptyDocumentCellTitle = defineAsyncComponent(
    () => import("./ItemTitle/EmptyDocumentCellTitle.vue"),
);
const OtherDocumentCellTitle = defineAsyncComponent(
    () => import("./ItemTitle/OtherDocumentCellTitle.vue"),
);

const props = defineProps<{
    item: FolderContentItem;
    is_quick_look_displayed: boolean;
}>();

const is_dropdown_displayed = ref<boolean>(false);
const table_row = ref<HTMLElement>();

const { folded_items_ids } = useState<Pick<RootState, "folded_items_ids">>(["folded_items_ids"]);

const is_folded = computed(() => folded_items_ids.value.includes(props.item.id));
const item_indentation = computed(() => {
    if (!props.item.level) {
        return {};
    }

    const indentation_size = props.item.level * 23;

    return {
        "padding-left": `${indentation_size}px`,
    };
});
const row_classes = computed(() => ({
    "document-tree-item-hidden": is_folded.value,
    "document-tree-item-created": props.item.created,
    "document-tree-item-updated": props.item.updated,
    "document-tree-item-uploading": props.item.is_uploading,
    "document-tree-item-folder": isFolder(props.item),
    "document-tree-item-file": isFile(props.item),
}));
const colspan = computed(() => (props.item.is_uploading ? 4 : 1));
const item_is_not_being_uploaded = computed(() => hasNoUploadingContent(props.item));
const is_item_uploading_in_quicklook_mode = computed(() =>
    isItemUploadingInQuickLookMode(props.item, props.is_quick_look_displayed),
);
const is_item_uploading_without_quick_look_mode = computed(() =>
    isItemUploadingInTreeView(props.item, props.is_quick_look_displayed),
);
const is_not_uploading_and_is_not_in_quicklook = computed(() =>
    isItemInTreeViewWithoutUpload(props.item, props.is_quick_look_displayed),
);

onMounted(() => {
    emitter.on("set-dropdown-shown", setIsDropdownDisplayed);
    if (!(props.item.created || props.item.is_uploading)) {
        return;
    }

    const magic_number_in_px_to_detect_if_we_partially_show_the_item = 20;
    const position_from_top =
        table_row.value.getBoundingClientRect().top +
        magic_number_in_px_to_detect_if_we_partially_show_the_item;
    const viewport_height = window.innerHeight || document.documentElement.clientHeight;
    const is_under_the_fold = position_from_top > viewport_height;

    if (is_under_the_fold) {
        emitter.emit("item-has-been-created-under-the-fold", {
            detail: { item: props.item },
        });
    }
});

function toggleQuickLookOnRow(event: MouseEvent): void {
    if (
        !is_dropdown_displayed.value &&
        (event.target.id === `document-folder-content-row-${props.item.id}` ||
            event.target.id === `document-folder-content-row-div-${props.item.id}`)
    ) {
        emitter.emit("toggle-quick-look", { details: { item: props.item } });
    }
}

function setIsDropdownDisplayed(event: { is_dropdown_shown: boolean }): void {
    is_dropdown_displayed.value = event.is_dropdown_shown;
}
</script>
