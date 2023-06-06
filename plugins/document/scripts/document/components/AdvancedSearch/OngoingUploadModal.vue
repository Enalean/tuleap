<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
    <div
        class="tlp-modal"
        role="dialog"
        aria-labelledby="document-search-ongoing-upload-title"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-search-ongoing-upload-title">
                {{ title }}
            </h1>
        </div>
        <div class="tlp-modal-body">
            <p>{{ explanations }}</p>
            <div class="tlp-alert-danger" v-for="error in error_messages" v-bind:key="error">
                {{ error }}
            </div>
            <global-upload-progress-bar
                class="document-search-ongoing-upload-progress"
                v-if="files_uploads_list.length > 0"
                v-bind:progress="progress"
                v-bind:nb_uploads_in_error="nb_uploads_in_error"
            />
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                v-bind:disabled="is_continue_button_disabled"
                data-dismiss="modal"
                class="tlp-button-primary tlp-modal-action"
                data-test="continue-button"
            >
                {{ continue_button }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import GlobalUploadProgressBar from "../Folder/ProgressBar/GlobalUploadProgressBar.vue";
import { useState } from "vuex-composition-helpers";
import type { RootState, FakeItem, ItemFile } from "../../type";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";

const { $gettext } = useGettext();

const title = ref($gettext("Ongoing upload"));
const continue_button = ref($gettext("Continue"));
const explanations = ref($gettext("Please wait until the ongoing upload is finished."));

const { files_uploads_list } = useState<Pick<RootState, "files_uploads_list">>([
    "files_uploads_list",
]);

const uploads_in_error = computed((): Array<ItemFile | FakeItem> => {
    return files_uploads_list.value.filter((upload) => upload.upload_error !== null);
});

const nb_uploads_in_error = computed((): number => {
    return uploads_in_error.value.length;
});

const progress = computed((): number => {
    return Math.round(
        files_uploads_list.value.reduce((sum_progress, upload): number => {
            if (upload.progress) {
                return sum_progress + upload.progress;
            }

            return sum_progress;
        }, 0) / files_uploads_list.value.length
    );
});

const nb_files_successfully_uploaded = computed((): number => {
    return files_uploads_list.value.reduce((nb, upload) => {
        if (upload.is_uploading) {
            return nb;
        }

        if (upload.upload_error) {
            return nb;
        }

        if (Math.round(upload.progress || 0) >= 100) {
            return nb + 1;
        }

        return nb;
    }, 0);
});

const is_continue_button_disabled = computed((): boolean => {
    if (files_uploads_list.value.length === 0) {
        return true;
    }
    const are_there_still_ongoing_uploads =
        files_uploads_list.value.length !==
        nb_files_successfully_uploaded.value + nb_uploads_in_error.value;

    if (are_there_still_ongoing_uploads) {
        return true;
    }

    return nb_uploads_in_error.value === 0;
});

const error_messages = computed((): string[] => {
    return uploads_in_error.value.map((upload) => upload.upload_error || "");
});

const modal_element = ref<InstanceType<typeof Element>>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

function close(): void {
    emit("close");
}

let modal: Modal;

onMounted(() => {
    if (!modal_element.value) {
        return;
    }

    modal = createModal(modal_element.value, {
        keyboard: false,
        dismiss_on_backdrop_click: false,
        destroy_on_hide: true,
    });
    modal.show();
    modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, close);
});

onBeforeUnmount(() => {
    modal.removeEventListener(EVENT_TLP_MODAL_HIDDEN, close);
});
</script>
