<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
        aria-labelledby="taskboard-error-modal-title"
        ref="modal_element"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="taskboard-error-modal-title">
                {{ $gettext("Oops, there's an issue") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>{{ $gettext("It seems an action you tried to perform can't be done") }}</p>
            <template v-if="has_more_details">
                <a
                    v-if="!is_more_shown"
                    class="taskboard-error-modal-link"
                    v-on:click="is_more_shown = true"
                    data-test="show-details"
                >
                    {{ $gettext("Show error details") }}
                </a>
                <pre class="taskboard-error-modal-message" v-if="is_more_shown" data-test="details">
                  {{ modal_error_message }}
                </pre>
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
                v-on:click="reloadPage"
            >
                <i class="fas fa-sync tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Reload the page") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { type Modal } from "tlp";
import { createModal } from "@tuleap/tlp-modal";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ErrorState } from "../../store/error/type";

const { modal_error_message } = useNamespacedState<Pick<ErrorState, "modal_error_message">>(
    "error",
    ["modal_error_message"],
);

let is_more_shown = ref(false);
let modal = ref<null | Modal>(null);

const modal_element = ref<InstanceType<typeof HTMLElement>>();
onMounted(() => {
    if (!(modal_element.value instanceof HTMLElement)) {
        return;
    }
    modal.value = createModal(modal_element.value, { destroy_on_hide: true });
    modal.value.show();
});

const has_more_details = computed((): boolean => {
    return modal_error_message.value.length > 0;
});

function reloadPage(): void {
    window.location.reload();
}
</script>
