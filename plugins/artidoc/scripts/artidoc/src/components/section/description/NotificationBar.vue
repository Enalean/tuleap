<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
  -
  -->
<template>
    <div>
        <span v-if="message" class="tlp-alert-danger">
            {{ message }}
        </span>
        <span v-else class="tlp-alert-info">{{ upload_message }}</span>
    </div>
</template>
<script setup lang="ts">
import { computed, toRefs, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { UploadFileStoreType } from "@/stores/useUploadFileStore";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";

export type NotificationBarProps = {
    upload_progress?: number;
    message?: string | null;
    file_name: string;
    file_id: string;
};

const props = withDefaults(defineProps<NotificationBarProps>(), {
    upload_progress: 0,
    message: "",
});

const { file_name, upload_progress, message } = toRefs(props);
const { deleteFinishedUpload } = strictInject<UploadFileStoreType>(UPLOAD_FILE_STORE);

watch(upload_progress, () => {
    if (upload_progress.value === 100) {
        setTimeout(() => {
            deleteFinishedUpload(props.file_id);
        }, 3_000);
    }
});

const upload_message = computed(() => `${file_name.value} : ${upload_progress.value}%`);
</script>
<style lang="scss" scoped>
@use "pkg:@tuleap/burningparrot-theme/css/includes/global-variables";

$title-height: 65px;

div {
    display: flex;
    position: sticky;
    justify-content: center;
}
</style>
