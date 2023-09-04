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
    <div>
        <h1 class="empty-state-title">
            {{ $gettext("Oops, there's an issue.") }}
        </h1>
        <p class="empty-state-text" v-if="!has_document_lock_error">
            {{ $gettext("It seems the content of this element can't be loaded.") }}
        </p>
        <p class="empty-state-text" v-else>
            {{ $gettext("It seems an action you tried to perform can't be done.") }}
        </p>
        <template v-if="has_any_loading_error">
            <div class="document-folder-error-link">
                <a
                    v-if="!is_more_shown"
                    data-test="error-details-show-more-button"
                    v-on:click.prevent="is_more_shown = true"
                    href="#"
                >
                    {{ $gettext("Show error details") }}
                </a>
            </div>
            <pre
                v-if="is_more_shown"
                class="document-folder-error-details"
                data-test="show-more-error-message"
                >{{ error_message }}</pre
            >
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useNamespacedGetters, useNamespacedState } from "vuex-composition-helpers";
import type { ErrorState } from "../../../store/error/module";
import type { ErrorGetters } from "../../../store/error/error-getters";

const is_more_shown = ref(false);

const {
    folder_loading_error,
    has_folder_loading_error,
    document_loading_error,
    has_document_lock_error,
    document_lock_error,
} = useNamespacedState<
    Pick<
        ErrorState,
        | "folder_loading_error"
        | "has_folder_loading_error"
        | "document_loading_error"
        | "has_document_lock_error"
        | "document_lock_error"
    >
>("error", [
    "folder_loading_error",
    "has_folder_loading_error",
    "document_loading_error",
    "has_document_lock_error",
    "document_lock_error",
]);

const { has_any_loading_error } = useNamespacedGetters<Pick<ErrorGetters, "has_any_loading_error">>(
    "error",
    ["has_any_loading_error"],
);

const error_message = computed((): string | null => {
    if (has_folder_loading_error.value) {
        return folder_loading_error.value;
    }

    if (has_document_lock_error.value) {
        return document_lock_error.value;
    }

    return document_loading_error.value;
});

defineExpose({
    error_message,
});
</script>
