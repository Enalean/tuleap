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
    <div class="tlp-form-element">
        <label class="tlp-label" for="document-new-file-upload">
            {{ $gettext("File") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <div class="tlp-form-element">
            <input
                type="file"
                id="document-new-file-upload"
                data-test="document-new-file-upload"
                name="file-upload"
                required
                v-on:change="onFileChange"
                ref="input"
            />
            <p class="tlp-text-danger" v-if="error_message.length > 0">
                {{ error_message }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import emitter from "../../../../helpers/emitter";
import type { FileProperties } from "../../../../type";
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { MAX_SIZE_UPLOAD } from "../../../../configuration-keys";

const { $gettext } = useGettext();

defineProps<{
    value: FileProperties;
}>();

const error_message = ref<string>("");
const input = ref<HTMLInputElement>();

const max_size_upload = strictInject(MAX_SIZE_UPLOAD);

function onFileChange(event: Event): void {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }
    const files = event.target.files || event.dataTransfer.files;
    if (files.length === 0) {
        return;
    }

    const file = files.item(0);
    if (file === null) {
        return;
    }
    emitter.emit("update-title-property", file.name);

    error_message.value = "";
    if (file.size > max_size_upload) {
        error_message.value = sprintf(
            $gettext("You are not allowed to upload files bigger than %s."),
            prettyKibibytes(max_size_upload),
        );
    }

    input.value?.setCustomValidity(error_message.value);

    emitter.emit("update-file-properties", { file });
}
</script>
