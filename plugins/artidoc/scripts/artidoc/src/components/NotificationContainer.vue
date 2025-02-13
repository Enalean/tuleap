<!--
  - * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
  - *
  - * This file is a part of Tuleap.
  - *
  - * Tuleap is free software; you can redistribute it and/or modify
  - * it under the terms of the GNU General Public License as published by
  - * the Free Software Foundation; either version 2 of the License, or
  - * (at your option) any later version.
  - *
  - * Tuleap is distributed in the hope that it will be useful,
  - * but WITHOUT ANY WARRANTY; without even the implied warranty of
  - * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - * GNU General Public License for more details.
  - *
  - * You should have received a copy of the GNU General Public License
  - * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="notification-container">
        <div class="notifications">
            <notification-message
                v-for="(message, index) in messages"
                v-bind:key="`notification-message-${index}`"
                v-bind:notification="message"
                v-bind:delete_notification="deleteNotification"
            />
            <div v-if="pending_uploads.length > 0" class="notification tlp-alert-info">
                <notification-progress
                    class="notification-progress"
                    v-for="upload in displayed_pending_uploads"
                    v-bind:key="upload.file_name"
                    v-bind:upload_progress="upload.progress"
                    v-bind:file_id="upload.file_id"
                    v-bind:file_name="upload.file_name"
                />
                <notification-remaining-pending-uploads
                    class="notification"
                    v-bind:pending_uploads="pending_uploads"
                    v-bind:nb_pending_upload_to_display="NB_PENDING_UPLOAD_TO_DISPLAY"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import NotificationProgress from "@/components/section/description/NotificationProgress.vue";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import { NOTIFICATION_COLLECTION } from "@/sections/notifications/notification-collection-injection-key";
import NotificationMessage from "@/components/section/description/NotificationMessage.vue";
import { computed } from "vue";
import NotificationRemainingPendingUploads from "@/components/NotificationRemainingPendingUploads.vue";

const { pending_uploads } = strictInject(FILE_UPLOADS_COLLECTION);
const { messages, deleteNotification } = strictInject(NOTIFICATION_COLLECTION);

const NB_PENDING_UPLOAD_TO_DISPLAY = 3;
const displayed_pending_uploads = computed(() =>
    pending_uploads.value.slice(0, NB_PENDING_UPLOAD_TO_DISPLAY),
);
</script>

<style lang="scss" scoped>
.notification-container {
    display: flex;
    position: sticky;
    z-index: 100;
    top: 7rem;
    justify-content: center;
    width: inherit;
}

.notifications {
    position: absolute;
    top: 1rem;

    > *:not(:last-child) {
        margin-bottom: 1rem;
    }
}

.notification-progress {
    display: flex;
    flex-flow: row nowrap;
    gap: var(--tlp-small-spacing);
    justify-content: space-between;
}

.notification {
    display: flex;
    flex-direction: column;
    gap: var(--tlp-small-spacing);
    width: 25rem;
}
</style>
