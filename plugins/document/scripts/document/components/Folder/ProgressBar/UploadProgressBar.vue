<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <div
        class="document-file-upload-progress"
        v-bind:class="{ 'document-file-upload-progress-canceled': is_canceled }"
    >
        <span class="document-file-upload-progress-value">{{ item.progress }}%</span>
        <progress
            class="document-file-upload-progress-bar"
            max="100"
            v-bind:value="item.progress"
        ></progress>
        <a
            class="document-file-upload-progress-cancel tlp-tooltip tlp-tooltip-left"
            href="#"
            v-bind:aria-label="cancel_title"
            v-bind:data-tlp-tooltip="cancel_title"
            role="button"
            v-on:click.prevent="cancel"
            data-test="cancel-upload"
        >
            <i class="fa-solid fa-circle-xmark"></i>
        </a>
    </div>
</template>

<script setup lang="ts">
import { isFolder } from "../../../helpers/type-check-helper";
import type { FakeItem } from "../../../type";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useActions } from "vuex-composition-helpers";

const props = defineProps<{ item: FakeItem }>();

const { $gettext } = useGettext();

const is_canceled = ref(false);

const cancel_title = computed((): string => {
    return $gettext("Cancel upload");
});

const { cancelFileUpload, cancelFolderUpload, cancelVersionUpload } = useActions([
    "cancelFileUpload",
    "cancelFolderUpload",
    "cancelVersionUpload",
]);

function cancel() {
    if (!is_canceled.value) {
        is_canceled.value = true;
        if (props.item.is_uploading_new_version) {
            cancelVersionUpload(props.item);
        } else if (!isFolder(props.item)) {
            cancelFileUpload(props.item);
        } else {
            cancelFolderUpload(props.item);
        }
    }
}
</script>
