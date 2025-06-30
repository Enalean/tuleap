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
            v-if="should_display_progress_bar"
            v-bind:data-tlp-tooltip="
                $gettext('Some files are being uploaded, click here to see the whole list.')
            "
            v-on:click="modal.show()"
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
import { computed, onMounted, ref } from "vue";
import { useGetters, useState, useStore } from "vuex-composition-helpers";
import type { RootState } from "../../../type";
import type { RootGetter } from "../../../store/getters";
import { templateRef } from "@vueuse/core";

const $store = useStore();

const modal = ref<Modal | null>(null);
const nb_uploads_in_error = ref<number>(0);
const uploads_modal = templateRef<InstanceType<FilesUploadsModal>>("uploads_modal");

const { files_uploads_list } = useState<Pick<RootState, "files_uploads_list">>([
    "files_uploads_list",
]);
const { global_upload_progress } = useGetters<Pick<RootGetter, "global_upload_progress">>([
    "global_upload_progress",
]);

const should_display_progress_bar = computed(
    () => files_uploads_list.value.filter((file) => file.upload_error === null).length > 0,
);

onMounted(() => {
    modal.value = createModal(uploads_modal.value.$el);

    $store.watch(
        (state) => state.files_uploads_list.filter((file) => file.upload_error !== null),
        (uploads_in_error) => {
            if (uploads_in_error.length > nb_uploads_in_error.value && !modal.value.is_shown) {
                modal.value.show();
            }

            nb_uploads_in_error.value = uploads_in_error.length;
        },
        { deep: true },
    );
});
</script>
