<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        class="tlp-form-element document-title-property"
        v-bind:class="{ 'tlp-form-element-disabled': is_root_folder }"
        data-test="document-new-item-title-form-element"
    >
        <label class="tlp-label" for="document-title">
            {{ $gettext("Title") }}
            <i class="fa-solid fa-asterisk"></i>
        </label>
        <input
            type="text"
            class="tlp-input"
            id="document-title"
            name="title"
            v-bind:placeholder="placeholder"
            required
            v-bind:value="value"
            v-bind:disabled="is_root_folder"
            v-on:input="oninput"
            ref="title_input"
            data-test="document-new-item-title"
        />
        <p class="tlp-text-danger" v-if="error_message.length > 0" data-test="title-error-message">
            {{ error_message }}
        </p>
    </div>
</template>
<script setup lang="ts">
import {
    doesDocumentAlreadyExistsAtUpdate,
    doesDocumentNameAlreadyExist,
    doesFolderNameAlreadyExist,
    doesFolderAlreadyExistsAtUpdate,
} from "../../../../../helpers/properties-helpers/check-item-title";
import { isFolder } from "../../../../../helpers/type-check-helper";
import type { Folder, Item, State } from "../../../../../type";
import emitter from "../../../../../helpers/emitter";
import { useState } from "vuex-composition-helpers";
import { computed, onMounted, ref, watch } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    value: string;
    parent: Folder;
    isInUpdateContext: boolean;
    currentlyUpdatedItem: Item;
}>();

const { folder_content } = useState<Pick<State, "folder_content">>(["folder_content"]);

const { $gettext } = useGettext();
const folder_already_exists_error = $gettext("A folder already exists with the same title.");
const document_already_exists_error = $gettext("A document already exists with the same title.");
const placeholder = ref($gettext("My document"));
const error_message = ref("");

const is_root_folder = computed((): boolean => {
    return props.currentlyUpdatedItem.parent_id === 0;
});

const title_input = ref<HTMLElement | null>(null);
watch(
    () => props.value,
    (value: string): void => {
        const error = checkTitleValidity(value);
        if (title_input.value instanceof HTMLInputElement) {
            title_input.value.setCustomValidity(error);
        }
        error_message.value = error;
    },
    { immediate: true },
);

onMounted((): void => {
    if (title_input.value instanceof HTMLInputElement) {
        title_input.value.focus();
    }
});

function checkTitleValidity(text_value: string): string {
    if (props.isInUpdateContext) {
        return getValidityErrorAtUpdate(text_value);
    }
    return getValidityErrorAtCreation(text_value);
}

function getValidityErrorAtCreation(text_value: string): string {
    if (
        isFolder(props.currentlyUpdatedItem) &&
        doesFolderNameAlreadyExist(text_value, folder_content.value, props.parent)
    ) {
        return folder_already_exists_error;
    } else if (
        !isFolder(props.currentlyUpdatedItem) &&
        doesDocumentNameAlreadyExist(text_value, folder_content.value, props.parent)
    ) {
        return document_already_exists_error;
    }

    return "";
}

function getValidityErrorAtUpdate(text_value: string): string {
    if (
        !isFolder(props.currentlyUpdatedItem) &&
        doesDocumentAlreadyExistsAtUpdate(
            text_value,
            folder_content.value,
            props.currentlyUpdatedItem,
            props.parent,
        )
    ) {
        return document_already_exists_error;
    } else if (
        isFolder(props.currentlyUpdatedItem) &&
        doesFolderAlreadyExistsAtUpdate(
            text_value,
            folder_content.value,
            props.currentlyUpdatedItem,
            props.parent,
        )
    ) {
        return folder_already_exists_error;
    }

    return "";
}

function oninput($event: Event): void {
    if ($event.target instanceof HTMLInputElement) {
        emitter.emit("update-title-property", $event.target.value);
    }
}
</script>
