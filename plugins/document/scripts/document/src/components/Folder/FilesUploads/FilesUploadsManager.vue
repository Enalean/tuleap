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
    <div>
        <div
            class="document-header-global-progress tlp-tooltip tlp-tooltip-left"
            v-if="global_upload_progress > 0"
            v-bind:data-tlp-tooltip="
                $gettext('Some files are being uploaded, click here to see the whole list.')
            "
            v-on:click="modal?.show()"
        >
            <global-upload-progress-bar
                v-bind:progress="global_upload_progress"
                v-bind:nb_uploads_in_error="nb_uploads_in_error"
            />
        </div>
        <files-uploads-modal ref="uploads_modal" />
    </div>
</template>
<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import FilesUploadsModal from "./FilesUploadsModal.vue";
import GlobalUploadProgressBar from "../ProgressBar/GlobalUploadProgressBar.vue";
import { computed, onMounted, ref, watch } from "vue";
import { useGetters, useState } from "vuex-composition-helpers";
import type { RootState } from "../../../type";
import type { RootGetter } from "../../../store/getters";

const modal = ref<Modal | null>(null);
const uploads_modal = ref<InstanceType<typeof FilesUploadsModal> | null>(null);

const { files_uploads_list } = useState<Pick<RootState, "files_uploads_list">>([
    "files_uploads_list",
]);
const { global_upload_progress } = useGetters<Pick<RootGetter, "global_upload_progress">>([
    "global_upload_progress",
]);

const nb_uploads_in_error = computed(
    () => files_uploads_list.value.filter((file) => file.upload_error !== null).length,
);

watch(nb_uploads_in_error, (nb_uploads_in_error) => {
    if (modal.value === null) {
        return;
    }
    if (nb_uploads_in_error > 0 && !modal.value.is_shown) {
        modal.value.show();
    }
});

onMounted(() => {
    if (uploads_modal.value !== null) {
        modal.value = createModal(uploads_modal.value.$el);
    }
});
</script>
