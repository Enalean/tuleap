<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  - This item is a part of Tuleap.
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
        aria-labelledby="max-size-threshold-modal-label"
        ref="max_size_modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="max-size-threshold-modal-label">
                {{ $gettext("Maximum archive size threshold exceeded") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-regular fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                {{
                    $gettext(
                        "The size of the zip file you are attempting to download is exceeding the threshold defined by the site administrators.",
                    )
                }}
            </p>
            <p>
                {{
                    $gettext(
                        "Contact your administrator or try to reorganize this folder, then try again.",
                    )
                }}
            </p>
            <div class="tlp-alert-danger">
                <span> {{ max_size_allowed_message }} </span>
                <br />
                <span data-test="download-as-zip-folder-size"> {{ archive_size_message }} </span>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                data-test="close-max-archive-size-threshold-exceeded-modal"
            >
                {{ $gettext("Got it") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import type { ConfigurationState } from "../../../../store/configuration";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";

const { interpolate, $gettext } = useGettext();

const props = defineProps<{ size: number }>();

const { max_archive_size } = useNamespacedState<Pick<ConfigurationState, "max_archive_size">>(
    "configuration",
    ["max_archive_size"],
);

const modal = ref<Modal | null>(null);

const max_size_modal = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (max_size_modal.value) {
        modal.value = createModal(max_size_modal.value, { destroy_on_hide: true });
        modal.value.addEventListener("tlp-modal-hidden", close);
        modal.value.show();
    }
});

onBeforeUnmount(() => {
    modal.value?.removeEventListener(EVENT_TLP_MODAL_HIDDEN, close);
});

const size_in_MB = computed((): string => {
    const size_in_mb = props.size / Math.pow(10, 6);
    return Number.parseFloat(size_in_mb.toString()).toFixed(2);
});

const archive_size_message = computed((): string => {
    const translated = $gettext("Size of the archive to be downloaded: %{ size_in_MB } MB");

    return interpolate(translated, { size_in_MB: size_in_MB.value });
});

const max_size_allowed_message = computed((): string => {
    const translated = $gettext("Maximum archive size allowed: %{ max_archive_size } MB");

    return interpolate(translated, { max_archive_size: max_archive_size.value });
});

const emit = defineEmits<{
    (e: "download-as-zip-modal-closed"): void;
}>();

function close(): void {
    emit("download-as-zip-modal-closed");
}
</script>
