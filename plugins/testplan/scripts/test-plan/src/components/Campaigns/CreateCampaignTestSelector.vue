<!--
  - Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" for="new-campaign-tests-selector">
            {{ $gettext("Tests") }}
            <i class="fa fa-asterisk" aria-hidden="true"></i>
        </label>
        <select
            id="new-campaign-tests-selector"
            v-model="selected_value"
            class="tlp-select"
            required
            data-test="new-campaign-tests"
            v-on:change="updateSelectedTests"
        >
            <option value="none">
                {{ $gettext("No tests") }}
            </option>
            <option value="all">
                {{ $gettext("All tests") }}
            </option>
            <option value="milestone" selected>
                {{
                    $gettext("All tests in %{ milestone_title }", {
                        milestone_title: milestone_title,
                    })
                }}
            </option>
            <optgroup
                v-if="display_test_definitions_tracker_reports_group_selector"
                v-bind:label="test_definitions_tracker_reports_group_label"
            >
                <option
                    v-for="tracker_report in testdefinition_tracker_reports"
                    v-bind:key="`tracker-report-${tracker_report.id}`"
                    v-bind:value="`${tracker_report.id}`"
                >
                    {{ tracker_report.label }}
                </option>
            </optgroup>
        </select>
    </div>
</template>
<script setup lang="ts">
import { useState } from "vuex-composition-helpers";
import type { State } from "../../store/type";
import type { CampaignInitialTests } from "../../helpers/Campaigns/campaign-initial-tests";
import type { TrackerReport } from "../../helpers/Campaigns/tracker-reports-retriever";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const { milestone_title, testdefinition_tracker_name } = useState<
    Pick<State, "milestone_title" | "testdefinition_tracker_name">
>(["milestone_title", "testdefinition_tracker_name"]);

const props = withDefaults(
    defineProps<{
        initial_tests: CampaignInitialTests;
        testdefinition_tracker_reports: TrackerReport[] | null;
    }>(),
    {
        initial_tests: () => {
            return { test_selector: "milestone" };
        },
    },
);

function transformCampaignInitialTestToStringValue(initial_tests: CampaignInitialTests): string {
    if (initial_tests.test_selector === "report") {
        return initial_tests.report_id.toString();
    }
    return initial_tests.test_selector;
}

const selected_value = ref(transformCampaignInitialTestToStringValue(props.initial_tests));

function getNbTrackerReports(): number {
    return props.testdefinition_tracker_reports === null
        ? 0
        : props.testdefinition_tracker_reports.length;
}

const { interpolate, $ngettext } = useGettext();
const test_definitions_tracker_reports_group_label = computed((): string => {
    return interpolate(
        $ngettext(
            "From %{ tracker_name } tracker report",
            "From %{ tracker_name } tracker reports",
            getNbTrackerReports(),
        ),
        { tracker_name: testdefinition_tracker_name.value },
    );
});

const display_test_definitions_tracker_reports_group_selector = computed((): boolean => {
    return getNbTrackerReports() > 0;
});

const emit = defineEmits<{
    (e: "update:initial_tests", value: CampaignInitialTests): void;
}>();
function updateSelectedTests(): void {
    let initial_tests: CampaignInitialTests;
    if (
        selected_value.value === "none" ||
        selected_value.value === "all" ||
        selected_value.value === "milestone"
    ) {
        initial_tests = { test_selector: selected_value.value };
    } else {
        initial_tests = {
            test_selector: "report",
            report_id: Number.parseInt(selected_value.value, 10),
        };
    }
    emit("update:initial_tests", initial_tests);
}
</script>
