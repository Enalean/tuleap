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
    <div
        class="document-upload-to-current-folder"
        v-bind:class="classes"
        data-test="document-current-folder-dropzone"
    >
        <div
            v-if="user_can_dragndrop_in_current_folder"
            class="document-upload-to-current-folder-message"
            data-test="document-current-folder-success-dropzone"
        >
            <i class="fa-solid fa-rotate-90 fa-share document-upload-to-current-folder-icon"></i>
            <p>{{ success_message }}</p>
        </div>
        <div
            v-else
            class="document-upload-to-current-folder-message"
            data-test="document-current-folder-error-dropzone"
        >
            <i class="fa-solid fa-ban document-upload-to-current-folder-icon"></i>
            <p data-test="document-current-folder-error-dropzone-message">{{ error_reason }}</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import prettyKibibytes from "pretty-kibibytes";
import { useGetters, useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import type { RootGetter } from "../../../store/getters";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps({
    user_can_dragndrop_in_current_folder: { type: Boolean, required: true },
    is_dropzone_highlighted: { type: Boolean, required: true },
    error_reason: { type: String, required: true },
});

const { max_files_dragndrop, max_size_upload } = useNamespacedState<
    Pick<ConfigurationState, "max_files_dragndrop" | "max_size_upload">
>("configuration", ["max_files_dragndrop", "max_size_upload"]);

const { current_folder_title } = useGetters<Pick<RootGetter, "current_folder_title">>([
    "current_folder_title",
]);

const { interpolate, $ngettext } = useGettext();

const success_message = computed((): string => {
    return interpolate(
        $ngettext(
            "Drop one file to upload it to %{folder}s (max size is %{size}s).",
            "Drop up to %{nb_files}s files to upload them to %{folder}s (max size is %{size}s).",
            max_files_dragndrop.value,
        ),
        {
            nb_files: max_files_dragndrop.value,
            folder: current_folder_title.value,
            size: prettyKibibytes(max_size_upload.value),
        },
    );
});

const upload_current_folder_class = computed((): string => {
    return props.user_can_dragndrop_in_current_folder ? "shown-success" : "shown-error";
});

const classes = computed((): string => {
    return props.is_dropzone_highlighted ? upload_current_folder_class.value : "";
});
</script>
