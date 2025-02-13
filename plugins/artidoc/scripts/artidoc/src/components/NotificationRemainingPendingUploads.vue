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
    <span v-if="remaining_uploads > 0">{{ remaining_uploads_message }}</span>
</template>

<script setup lang="ts">
import { computed, toRefs } from "vue";
import type { OnGoingUploadFileWithId } from "@/sections/attachments/FileUploadsCollection";
import { useGettext } from "vue3-gettext";

export type NotificationRemainingProps = {
    pending_uploads: OnGoingUploadFileWithId[];
    nb_pending_upload_to_display: number;
};
const props = defineProps<NotificationRemainingProps>();
const { pending_uploads } = toRefs(props);
const { $ngettext } = useGettext();

const remaining_uploads = computed(
    () =>
        pending_uploads.value.slice(
            props.nb_pending_upload_to_display,
            pending_uploads.value.length,
        ).length,
);
const remaining_uploads_message = computed(() =>
    $ngettext(
        "There is %{ remaining_uploads } other upload in progress...",
        "There are %{ remaining_uploads } other uploads in progress...",
        remaining_uploads.value,
        { remaining_uploads: remaining_uploads.value.toString() },
    ),
);
</script>
