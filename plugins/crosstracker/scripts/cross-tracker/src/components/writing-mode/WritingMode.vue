<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="writing-mode">
        <query-editor
            v-bind:writing_cross_tracker_report="writing_cross_tracker_report"
            v-on:trigger-search="search"
            ref="editor"
        />
        <div class="actions">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline"
                v-on:click="cancel"
                data-test="writing-mode-cancel-button"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary"
                v-on:click="search"
                data-test="search-report-button"
            >
                <i aria-hidden="true" class="fa-solid fa-search tlp-button-icon"></i>
                {{ $gettext("Search") }}
            </button>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import QueryEditor from "./QueryEditor.vue";
import type { WritingCrossTrackerReport } from "../../domain/WritingCrossTrackerReport";

const { $gettext } = useGettext();

defineProps<{ writing_cross_tracker_report: WritingCrossTrackerReport }>();
const emit = defineEmits<{
    (e: "preview-result"): void;
    (e: "cancel-query-edition"): void;
}>();

const editor = ref<InstanceType<typeof QueryEditor>>();

function cancel(): void {
    emit("cancel-query-edition");
}

function search(): void {
    emit("preview-result");
}
</script>

<style scoped lang="scss">
.writing-mode {
    display: flex;
    flex-direction: column;
    gap: var(--tlp-medium-spacing);
}

.actions {
    display: flex;
    justify-content: center;
    gap: var(--tlp-medium-spacing);
}
</style>
