<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
  -
  -->

<template>
    <div class="tlp-pane-container planned-iteration-display">
        <div
            class="tlp-pane-header planned-iteration-header"
            v-on:click="toggleIsOpen"
            data-test="iteration-card-header"
        >
            <span
                class="tlp-pane-title planned-iteration-header-label"
                data-test="iteration-header-label"
            >
                <i
                    class="tlp-pane-title-icon fas fa-fw"
                    v-bind:class="[is_open ? 'fa-caret-down' : 'fa-caret-right']"
                    data-test="planned-iteration-toggle-icon"
                    aria-hidden="true"
                />
                {{ iteration.title }}
            </span>
            <div>
                <span
                    v-if="iteration.start_date !== null"
                    class="planned-iteration-header-dates"
                    data-test="iteration-header-dates"
                >
                    {{ formatDate(iteration.start_date) }}
                    <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
                    {{ formatDate(iteration.end_date) }}
                </span>
                <span
                    v-if="iteration.status.length > 0"
                    class="tlp-badge-outline tlp-badge-primary"
                    data-test="iteration-header-status"
                >
                    {{ iteration.status }}
                </span>
            </div>
        </div>
        <div
            class="planned-iteration-info"
            v-if="is_open && iteration.user_can_update"
            data-test="planned-iteration-info"
        >
            <a
                v-bind:href="edition_url"
                class="planned-iteration-info-link"
                v-bind:title="$gettext('Edit')"
                data-test="planned-iteration-info-edit-link"
            >
                <i
                    class="fas fa-pencil-alt planned-iteration-info-link-icon"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Edit") }}
            </a>
        </div>
        <section
            class="tlp-pane-section planned-iteration-content"
            v-if="is_open"
            data-test="planned-iteration-content"
        >
            <iteration-user-story-list v-bind:iteration="iteration" />
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { formatDateYearMonthDay } from "@tuleap/date-helper";
import { buildIterationEditionUrl } from "../../../helpers/create-new-iteration-link-builder";
import IterationUserStoryList from "./IterationUserStoryList.vue";
import type { Iteration } from "../../../type";
import type { ProgramIncrement } from "../../../store/configuration";

const props = defineProps<{
    iteration: Iteration;
}>();

const { program_increment, user_locale } = useNamespacedState<{
    program_increment: ProgramIncrement;
    user_locale: string;
}>("configuration", ["program_increment", "user_locale"]);

const is_open = ref(false);

function formatDate(date: string): string {
    return formatDateYearMonthDay(user_locale.value, date);
}

function toggleIsOpen(): void {
    is_open.value = !is_open.value;
}

const edition_url = computed((): string =>
    buildIterationEditionUrl(props.iteration.id, program_increment.value.id),
);
</script>
