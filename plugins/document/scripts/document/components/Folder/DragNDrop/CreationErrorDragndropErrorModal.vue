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
    <error-modal
        v-on:error-modal-hidden="bubbleErrorModalHidden"
        v-bind:body_class="'document-uploads-files-list'"
    >
        <span slot="modal-title"> {{ error_message }}</span>

        <div
            v-for="reason of sorted_reasons"
            v-bind:key="reason.filename"
            class="tlp-pane-section document-dragndrop-file-upload document-dragndrop-file-upload-error"
        >
            <div class="document-uploads-file">
                <span class="document-uploads-file-title">{{ reason.filename }}</span>
                <span class="document-uploads-file-error-message">
                    {{ reason.message }}
                </span>
            </div>
        </div>
    </error-modal>
</template>

<script setup lang="ts">
import ErrorModal from "./ErrorModal.vue";
import type { Reason } from "../../../type";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ reasons: Array<Reason> }>();

const { $ngettext } = useGettext();

const sorted_reasons = computed(() => {
    return [...props.reasons].sort((a: Reason, b: Reason): number => {
        if (!a.filename || !b.filename) {
            return 0;
        }
        return a.filename.localeCompare(b.filename, undefined, { numeric: true });
    });
});

const emit = defineEmits<{
    (e: "error-modal-hidden"): void;
}>();

function bubbleErrorModalHidden(): void {
    emit("error-modal-hidden");
}

const error_message = computed((): string => {
    return $ngettext(
        "Oops… Unable to upload file",
        "Oops… Unable to upload files",
        props.reasons.length,
    );
});
</script>
