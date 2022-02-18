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
    <div class="test-plan-campaign-progressions">
        <div
            class="test-plan-campaign-progression passed"
            v-bind:class="passed_classname"
            v-if="campaign.nb_of_passed"
            v-bind:aria-label="passed_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_passed }}</div>
        </div>
        <div
            class="test-plan-campaign-progression failed"
            v-bind:class="failed_classname"
            v-if="campaign.nb_of_failed"
            v-bind:aria-label="failed_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_failed }}</div>
        </div>
        <div
            class="test-plan-campaign-progression blocked"
            v-bind:class="blocked_classname"
            v-if="campaign.nb_of_blocked"
            v-bind:aria-label="blocked_title"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_blocked }}</div>
        </div>
        <div
            class="test-plan-campaign-progression notrun"
            v-bind:class="notrun_classname"
            v-if="should_not_run_progress_be_displayed"
            v-bind:aria-label="notrun_title"
            data-test="progress-not-run"
        >
            <div class="test-plan-campaign-progression-bar"></div>
            <div class="test-plan-campaign-progression-value">{{ campaign.nb_of_notrun }}</div>
        </div>
    </div>
</template>
<script setup lang="ts">
import type { Campaign } from "../../type";
import { computed } from "@vue/composition-api";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";

const props = defineProps<{
    campaign: Campaign;
}>();

const percentage_classname_prefix = "test-plan-campaign-progression-width-";

const nb_tests = computed((): number => {
    return (
        props.campaign.nb_of_blocked +
        props.campaign.nb_of_failed +
        props.campaign.nb_of_notrun +
        props.campaign.nb_of_passed
    );
});

function percentage(nb: number): string {
    if (!nb) {
        return "";
    }

    return percentage_classname_prefix + Math.round((nb * 100) / nb_tests.value);
}

const should_not_run_progress_be_displayed = computed((): boolean => {
    if (nb_tests.value === 0) {
        return true;
    }

    return props.campaign.nb_of_notrun > 0;
});

const passed_classname = computed((): string => {
    return percentage(props.campaign.nb_of_passed);
});

const blocked_classname = computed((): string => {
    return percentage(props.campaign.nb_of_blocked);
});

const failed_classname = computed((): string => {
    return percentage(props.campaign.nb_of_failed);
});

const notrun_classname = computed((): string => {
    if (nb_tests.value === 0) {
        return percentage_classname_prefix + 100;
    }

    return percentage(props.campaign.nb_of_notrun);
});

const { interpolate, $ngettext } = useGettext();
const passed_title = computed((): string => {
    return interpolate($ngettext("%{ nb } passed", "%{ nb } passed", nb_tests.value), {
        nb: props.campaign.nb_of_passed,
    });
});

const blocked_title = computed((): string => {
    return interpolate($ngettext("%{ nb } blocked", "%{ nb } blocked", nb_tests.value), {
        nb: props.campaign.nb_of_blocked,
    });
});

const failed_title = computed((): string => {
    return interpolate($ngettext("%{ nb } failed", "%{ nb } failed", nb_tests.value), {
        nb: props.campaign.nb_of_failed,
    });
});

const notrun_title = computed((): string => {
    return interpolate($ngettext("%{ nb } not run", "%{ nb } not run", nb_tests.value), {
        nb: props.campaign.nb_of_notrun,
    });
});
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
