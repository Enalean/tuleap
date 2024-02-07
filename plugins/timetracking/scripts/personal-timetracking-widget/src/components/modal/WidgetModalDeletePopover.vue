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
    <div>
        <button
            class="tlp-button-outline tlp-button-small tlp-button-danger"
            ref="popover_button"
            data-placement="left"
            data-trigger="click"
            data-test="timetracking-delete-time"
        >
            <i class="fas fa-trash-alt"></i>
        </button>
        <section class="tlp-popover tlp-popover-danger" ref="delete_popover">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">{{ $gettext("Wait a minute...") }}</h1>
            </div>
            <div class="tlp-popover-body">
                <p>
                    {{ $gettext("You're about to remove the time. Please confirm your action.") }}
                </p>
            </div>
            <div class="tlp-popover-footer">
                <button
                    type="button"
                    class="tlp-button-danger tlp-button-outline"
                    data-dismiss="popover"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="button"
                    class="tlp-button-danger"
                    v-on:click="removeTime"
                    data-dismiss="popover"
                    data-test="timetracking-confirm-time-deletion"
                >
                    {{ $gettext("Confirm deletion") }}
                </button>
            </div>
        </section>
    </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from "vue";
import type { Ref } from "vue";
import { createPopover } from "@tuleap/tlp-popovers";
import { usePersonalTimetrackingWidgetStore } from "../../store/root";

const props = defineProps<{
    timeId: number;
}>();

const popover_button: Ref<HTMLElement | undefined> = ref();
const delete_popover: Ref<HTMLElement | undefined> = ref();

const personal_store = usePersonalTimetrackingWidgetStore();

onMounted(() => {
    if (
        !(popover_button.value instanceof HTMLElement) ||
        !(delete_popover.value instanceof HTMLElement)
    ) {
        return;
    }

    createPopover(popover_button.value, delete_popover.value);
});

const removeTime = (): void => {
    personal_store.deleteTime(props.timeId);
};
</script>
