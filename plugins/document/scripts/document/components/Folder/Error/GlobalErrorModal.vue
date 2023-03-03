<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="document-error-modal-title"
        ref="root_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="document-error-modal-title">
                {{ $gettext("Oops, there's an issue.") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>{{ $gettext("It seems an action you tried to perform can't be done.") }}</p>
            <template v-if="has_more_details">
                <a
                    v-if="!is_more_shown"
                    class="document-error-modal-link"
                    v-on:click="is_more_shown = true"
                    data-test="show-details"
                >
                    {{ $gettext("Show error details") }}
                </a>
                <pre v-if="is_more_shown" data-test="details">{{ global_modal_error_message }}</pre>
            </template>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Close") }}
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                data-test="reload"
                v-on:click="reloadPage"
            >
                <i class="fa-solid fa-rotate tlp-button-icon"></i>
                {{ $gettext("Reload the page") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { createModal } from "@tuleap/tlp-modal";
import { computed, ref, onMounted } from "vue";
import { useNamespacedMutations, useNamespacedState } from "vuex-composition-helpers";
import type { ErrorState } from "../../../store/error/module";

const is_more_shown = ref(false);

const { global_modal_error_message } = useNamespacedState<
    Pick<ErrorState, "global_modal_error_message">
>("error", ["global_modal_error_message"]);

const { resetErrors } = useNamespacedMutations("error", ["resetErrors"]);

const has_more_details = computed((): boolean => {
    if (!global_modal_error_message.value) {
        return false;
    }
    return global_modal_error_message.value.length > 0;
});

const root_element = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (root_element.value) {
        const modal = createModal(root_element.value, { destroy_on_hide: true });
        modal.show();
        modal.addEventListener("tlp-modal-hidden", reset);
    }
});

function reset() {
    resetErrors();
}
function reloadPage(): void {
    window.location.reload();
}
</script>
