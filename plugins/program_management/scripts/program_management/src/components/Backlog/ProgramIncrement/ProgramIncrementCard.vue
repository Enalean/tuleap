<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div class="tlp-pane-container program-increment-display">
        <div
            class="tlp-pane-header program-increment-header"
            v-on:click="toggleIsOpen"
            data-test="program-increment-toggle"
        >
            <span class="tlp-pane-title program-increment-header-label">
                <i
                    class="tlp-pane-title-icon fas fa-fw"
                    v-bind:class="[is_open ? 'fa-caret-down' : 'fa-caret-right']"
                    data-test="program-increment-toggle-icon"
                    aria-hidden="true"
                />
                {{ increment.title }}
            </span>
            <div>
                <span
                    class="program-increment-header-dates"
                    v-if="increment.start_date !== null && increment.end_date !== null"
                >
                    {{ formatDate(increment.start_date) }}
                    <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                    {{ formatDate(increment.end_date) }}
                </span>
                <span class="tlp-badge-outline tlp-badge-primary">{{ increment.status }}</span>
            </div>
        </div>
        <div
            class="program-increment-info"
            v-if="is_open && increment.user_can_update"
            data-test="program-increment-info"
        >
            <a
                v-bind:href="`/plugins/tracker/?aid=${increment.id}&program_increment=update`"
                class="program-increment-info-link"
                v-bind:title="$gettext('Edit')"
                data-not-drag-handle="true"
                data-test="program-increment-info-edit-link"
            >
                <i
                    class="fas fa-pencil-alt program-increment-info-link-icon"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Edit") }}
            </a>
            <a
                v-if="is_iteration_tracker_defined"
                v-bind:href="`/program_management/${short_name}/increments/${increment.id}/plan`"
                class="program-increment-info-link"
                v-bind:title="planned_iteration_link"
                data-not-drag-handle="true"
                data-test="program-increment-plan-iterations-link"
            >
                <i
                    class="fas fa-sign-in-alt program-increment-info-link-icon"
                    aria-hidden="true"
                ></i>
                {{ planned_iteration_link }}
            </a>
        </div>
        <section
            class="tlp-pane-section program-increment-content"
            v-if="is_open"
            data-test="program-increment-content"
        >
            <program-increment-feature-list v-if="is_open" v-bind:increment="increment" />
        </section>
    </div>
</template>
<script setup lang="ts">
import { computed, ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { formatDateYearMonthDay } from "@tuleap/date-helper";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import ProgramIncrementFeatureList from "./ProgramIncrementFeatureList.vue";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

const { user_locale, short_name, tracker_iteration_label, is_iteration_tracker_defined } =
    useNamespacedState<ConfigurationState>("configuration", [
        "user_locale",
        "short_name",
        "tracker_iteration_label",
        "is_iteration_tracker_defined",
    ]);

defineProps<{ increment: ProgramIncrement }>();

const is_open = ref(false);

function formatDate(date: string): string {
    return formatDateYearMonthDay(user_locale.value, date);
}

function toggleIsOpen(): void {
    is_open.value = !is_open.value;
}

const planned_iteration_link = computed((): string =>
    $gettext("Plan %{ iteration_label }", { iteration_label: tracker_iteration_label.value }),
);
</script>
