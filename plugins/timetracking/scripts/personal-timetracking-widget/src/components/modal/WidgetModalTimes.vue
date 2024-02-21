<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <td class="tlp-table-cell-actions">
        <a
            v-on:click.prevent="show_modal"
            v-bind:href="link_to_artifact_timetracking"
            data-test="timetracking-details"
        >
            {{ $gettext("Details") }}
        </a>

        <div class="tlp-modal" role="dialog" ref="timetracking_modal">
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title">
                    {{ $gettext("Detailed times") }}
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <widget-modal-content
                v-if="artifact"
                data-test="modal-content"
                v-bind:artifact="artifact"
                v-bind:project="project"
                v-bind:time-data="times[0]"
            />
            <div class="tlp-modal-footer tlp-modal-footer-large">
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                >
                    {{ $gettext("Close") }}
                </button>
            </div>
        </div>
    </td>
</template>
<script setup lang="ts">
import WidgetModalContent from "./WidgetModalContent.vue";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";
import type { Ref } from "vue";
import { computed, onMounted, ref } from "vue";
import { createModal, Modal } from "@tuleap/tlp-modal";
import { usePersonalTimetrackingWidgetStore } from "../../store/root";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();
const props = defineProps<{
    artifact: Artifact | null;
    project: ProjectResponse;
    times: PersonalTime[];
}>();

const timetracking_modal: Ref<HTMLElement | undefined> = ref();
const modal_simple_content: Ref<Modal | undefined> = ref();
const personal_store = usePersonalTimetrackingWidgetStore();

const link_to_artifact_timetracking = computed((): string => {
    if (!props.artifact) {
        return "";
    }

    return props.artifact.html_url + "&view=timetracking";
});

onMounted((): void => {
    if (!(timetracking_modal.value instanceof HTMLElement)) {
        return;
    }

    modal_simple_content.value = createModal(timetracking_modal.value);
    if (!(modal_simple_content.value instanceof Modal)) {
        return;
    }

    modal_simple_content.value.addEventListener("tlp-modal-hidden", () => {
        personal_store.setAddMode(false);
        personal_store.reloadTimes();
    });
});

const show_modal = (): void => {
    if (!(modal_simple_content.value instanceof Modal)) {
        return;
    }

    personal_store.setCurrentTimes(props.times);
    modal_simple_content.value.toggle();
};
</script>
