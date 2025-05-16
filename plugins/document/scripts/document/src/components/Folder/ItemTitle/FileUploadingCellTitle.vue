<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
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
    <div class="document-file-upload-cell-title document-file-upload-fake-item-cell-title">
        <span>
            <fake-caret v-bind:item="item" />
            <i class="fa-fw document-folder-content-icon" v-bind:class="icon_class"></i>
            {{ item.title }}
        </span>
        <upload-progress-bar v-bind:item="item" />
    </div>
</template>

<script setup lang="ts">
import FakeCaret from "./FakeCaret.vue";
import { iconForMimeType } from "../../../helpers/icon-for-mime-type";
import UploadProgressBar from "../ProgressBar/UploadProgressBar.vue";
import { computed } from "vue";
import { ICON_EMPTY } from "../../../constants";
import type { FakeItem } from "../../../type";

const props = defineProps<{ item: FakeItem }>();

const icon_class = computed((): string => {
    if (!props.item.file_type) {
        return ICON_EMPTY;
    }
    return iconForMimeType(props.item.file_type);
});
</script>
