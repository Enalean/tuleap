<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="test-plan-backlog-item-coverage">
        <template v-if="backlog_item.is_loading_test_definitions">
            <span class="tlp-skeleton-text test-plan-backlog-item-coverage-text-skeleton"></span>
            <span class="tlp-skeleton-text test-plan-backlog-item-coverage-icon-skeleton"></span>
        </template>
        <template v-else-if="test_status !== null">
            <span class="test-plan-backlog-item-coverage-text" data-test="nb-tests">
                {{ nb_tests_title }}
            </span>
            <span
                class="test-plan-backlog-item-coverage-icon"
                v-bind:class="stack_class"
                data-test="backlog-item-icon"
            >
                <i
                    class="fa test-plan-backlog-item-coverage-icon-symbol"
                    v-bind:class="icon_class"
                ></i>
            </span>
        </template>
    </div>
</template>
<script setup lang="ts">
import type { BacklogItem } from "../../type";
import type { TestStats } from "../../helpers/BacklogItems/compute-test-stats";
import {
    computeTestStats,
    getTestStatusFromStats,
} from "../../helpers/BacklogItems/compute-test-stats";
import { computed } from "@vue/composition-api";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const props = defineProps<{
    backlog_item: BacklogItem;
}>();

const stats = computed((): Readonly<TestStats> => {
    return computeTestStats(props.backlog_item);
});

const nb_tests = computed((): number => {
    return Object.values(stats.value).reduce((a: number, b: number): number => {
        return a + b;
    });
});

const { interpolate, $ngettext } = useGettext();

const nb_tests_title = computed((): string => {
    const nb = nb_tests.value;
    return interpolate($ngettext("%{ nb } planned test", "%{ nb } planned tests", nb), { nb });
});

const test_status = computed((): keyof TestStats | null => {
    return getTestStatusFromStats(stats.value);
});

const icon_class = computed((): string => {
    const value = test_status.value;
    switch (value) {
        case null:
            return "";
        case "failed":
            return "fa-times-circle";
        case "blocked":
            return "fa-exclamation-circle";
        case "notrun":
            return "fa-question-circle";
        case "passed":
            return "fa-check-circle";
        default:
            return ((val: never): never => val)(value);
    }
});

const stack_class = computed((): string => {
    const value = test_status.value;
    switch (value) {
        case null:
            return "";
        case "failed":
            return "test-plan-backlog-item-coverage-icon-failed";
        case "blocked":
            return "test-plan-backlog-item-coverage-icon-blocked";
        case "notrun":
            return "test-plan-backlog-item-coverage-icon-notrun";
        case "passed":
            return "test-plan-backlog-item-coverage-icon-passed";
        default:
            return ((val: never): never => val)(value);
    }
});
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
