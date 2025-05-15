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
        class="tlp-modal tlp-modal-warning"
        role="dialog"
        aria-labelledby="modal-archive-size-warning-label"
        ref="warning_size_modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-archive-size-warning-label">
                {{ modal_header_title }}
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
            <div class="download-modal-multiple-warnings-section">
                <h2 class="tlp-modal-subtitle">
                    <span class="tlp-badge-warning download-modal-warning-number">1</span>
                    <span>{{ $gettext("Archive size warning threshold reached") }}</span>
                </h2>
                <div class="download-modal-multiple-warnings-content">
                    <p>{{ warning_threshold_message }}</p>
                    <p>
                        {{
                            $gettext(
                                "Depending on the speed of your network, it can take some time to complete. Do you want to continue?",
                            )
                        }}
                    </p>
                    <div class="tlp-alert-warning" data-test="download-as-zip-folder-size-warning">
                        {{ archive_size_message }}
                    </div>
                </div>
            </div>
            <div class="download-modal-multiple-warnings-section" v-if="shouldWarnOsxUser">
                <h2 class="tlp-modal-subtitle">
                    <span class="tlp-badge-warning download-modal-warning-number">2</span>
                    <span>{{ $gettext("We detect you are using OSX") }}</span>
                </h2>
                <div class="download-modal-multiple-warnings-content">
                    <p>
                        {{
                            $gettext(
                                "The archive you want to download has a size greater than or equal to 4GB or contains more than 64000 files.",
                            )
                        }}"
                    </p>
                    <p>
                        {{
                            $gettext(
                                "Please note that the OSX archive extraction tool might not succeed in opening it.",
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                data-test="close-archive-size-warning"
            >
                {{ $gettext("Cancel") }}
            </button>
            <a
                type="button"
                download
                v-bind:href="folderHref"
                class="tlp-button-warning tlp-button-primary tlp-modal-action"
                data-dismiss="modal"
                data-test="confirm-download-archive-button-despite-size-warning"
            >
                {{ $gettext("Download anyway") }}
            </a>
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

const props = defineProps<{ size: number; folderHref: string; shouldWarnOsxUser: boolean }>();

const { warning_threshold } = useNamespacedState<Pick<ConfigurationState, "warning_threshold">>(
    "configuration",
    ["warning_threshold"],
);

const size_in_MB = computed((): string => {
    const size_in_mb = props.size / Math.pow(10, 6);
    return Number.parseFloat(size_in_mb.toString()).toFixed(2);
});

const { interpolate, $ngettext, $gettext } = useGettext();

const modal_header_title = computed((): string => {
    const nb_warnings = props.shouldWarnOsxUser ? 2 : 1;
    const translated = $ngettext("1 warning", "%{ nb_warnings } warnings", nb_warnings);

    return interpolate(translated, { nb_warnings });
});

const warning_threshold_message = computed((): string => {
    const translated = $gettext(
        "The archive you want to download has a size greater than %{ warning_threshold } MB.",
    );

    return interpolate(translated, { warning_threshold: warning_threshold.value });
});

const archive_size_message = computed((): string => {
    const translated = $gettext("Size of the archive to be downloaded: %{ size_in_MB } MB");

    return interpolate(translated, { size_in_MB: size_in_MB.value });
});

const modal = ref<Modal | null>(null);

const warning_size_modal = ref<InstanceType<typeof HTMLElement>>();

onMounted((): void => {
    if (warning_size_modal.value) {
        modal.value = createModal(warning_size_modal.value, { destroy_on_hide: true });
        modal.value.addEventListener("tlp-modal-hidden", close);
        modal.value.show();
    }
});

onBeforeUnmount(() => {
    modal.value?.removeEventListener(EVENT_TLP_MODAL_HIDDEN, close);
});
const emit = defineEmits<{
    (e: "download-folder-as-zip-modal-closed"): void;
}>();

function close(): void {
    emit("download-folder-as-zip-modal-closed");
}
</script>
