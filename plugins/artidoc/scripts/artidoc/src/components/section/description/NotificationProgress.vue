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
    <div class="notification-message">
        <span class="upload-message">{{ upload_message }}</span
        ><span class="upload-percentage">{{ upload_percentage }}</span>
    </div>
</template>
<script setup lang="ts">
import { computed, toRefs, watch } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";

export type NotificationProgressProps = {
    upload_progress?: number;
    file_name: string;
    file_id: string;
};

const props = withDefaults(defineProps<NotificationProgressProps>(), {
    upload_progress: 0,
});

const { file_name, upload_progress, file_id } = toRefs(props);
const { deleteUpload } = strictInject(FILE_UPLOADS_COLLECTION);
watch(
    [upload_progress, file_id],
    () => {
        if (upload_progress.value === 100) {
            setTimeout(() => {
                deleteUpload(file_id.value);
            }, 3_000);
        }
    },
    { immediate: true },
);

const upload_message = computed(() => `${file_name.value} `);
const upload_percentage = computed(() => `${upload_progress.value}%`);
</script>
<style lang="scss" scoped>
.upload-message {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-message {
    display: flex;
}
</style>
