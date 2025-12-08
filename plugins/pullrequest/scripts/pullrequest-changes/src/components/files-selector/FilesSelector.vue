<!--
  - Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
    <tuleap-lazybox ref="file_selector" />
</template>

<script setup lang="ts">
import "@tuleap/lazybox";
import { ref, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import { type Lazybox } from "@tuleap/lazybox";
import type { PullRequestFile } from "../../api/rest-querier";
import { getPullRequestFileTemplate } from "./pull-request-file-template";
import { isPullRequestFile } from "./is-pull-request-file";
import { getFilesFilter } from "./FilesFilter";

const props = defineProps<{
    files: readonly PullRequestFile[];
    selected_file: PullRequestFile;
}>();

const { $gettext } = useGettext();
const emit = defineEmits<{
    (e: "file-selected", file: PullRequestFile): void;
}>();

const file_selector = ref<HTMLElement & Lazybox>();

const file_selector_items = props.files.map((file) => ({
    value: file,
    is_disabled: false,
}));

const group = {
    label: $gettext("Files"),
    empty_message: $gettext("No files found."),
    is_loading: false,
    items: file_selector_items,
    footer_message: "",
};

const files_filter = getFilesFilter(group, file_selector_items);

onMounted(() => {
    if (file_selector.value) {
        file_selector.value.options = {
            is_multiple: false,
            placeholder: "",
            templating_callback: getPullRequestFileTemplate,
            selection_callback(selected_value: unknown[]): void {
                if (!isPullRequestFile(selected_value[0])) {
                    return;
                }
                emit("file-selected", selected_value[0]);
            },
            search_input_placeholder: $gettext("Search files by their name."),
            search_input_callback(query: string): void {
                if (!file_selector.value) {
                    throw new Error("[pull-request-changes] Lazybox instance not found.");
                }
                return files_filter.filterFiles(file_selector.value, query);
            },
        };

        file_selector.value.replaceDropdownContent([group]);
        file_selector.value.replaceSelection([{ value: props.selected_file, is_disabled: false }]);
    }
});
</script>

<style lang="scss">
@use "pkg:@tuleap/lazybox";

.lazybox-selected-value-remove-button {
    display: none;
}

.pull-request-file {
    display: flex;
    flex: 1;
}

.pull-request-file-path {
    flex: 1 1 auto;
    text-align: left;
}

.pull-request-file-status {
    display: inline-block;
    flex: 0 0 auto;
    margin: 0 var(--tlp-small-spacing) 0 0;
    font-family: var(--tlp-font-family-mono);
    font-size: 1rem;
    font-weight: 600;
    text-align: left;

    &.pull-request-file-status-added {
        color: var(--tlp-success-color);
    }

    &.pull-request-file-status-modified {
        color: var(--tlp-warning-color);
    }

    &.pull-request-file-status-deleted {
        color: var(--tlp-danger-color);
    }
}

.lazybox-dropdown-option-value:focus > .pull-request-file > .pull-request-file-changes,
.lazybox-dropdown-option-value:hover > .pull-request-file > .pull-request-file-changes {
    color: var(--tlp-white-color);
    font-weight: 500;
}

.pull-request-file-changes {
    flex: 0 0 auto;
    width: auto;
    min-width: 50px;
    font-variant-numeric: tabular-nums;

    &.hidden {
        visibility: hidden;
    }

    &.pull-request-file-lines-added {
        margin: 0 5px 0 0;
        text-align: right;
    }
}
</style>
