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
    <div class="notifications">
        <notification-bar
            v-for="upload in pending_uploads"
            v-bind:key="upload.file_name"
            v-bind:upload_progress="upload.progress"
            v-bind:file_id="upload.file_id"
            v-bind:file_name="upload.file_name"
        />
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { UPLOAD_FILE_STORE } from "@/stores/upload-file-store-injection-key";
import NotificationBar from "@/components/section/description/NotificationBar.vue";

const { pending_uploads } = strictInject(UPLOAD_FILE_STORE);
</script>

<style lang="scss" scoped>
.notifications {
    position: fixed;
    z-index: 100;
    top: 5rem;
    left: 50%;
    transform: translateX(-50%);

    > *:not(:last-child) {
        margin-bottom: 1rem;
    }
}
</style>
