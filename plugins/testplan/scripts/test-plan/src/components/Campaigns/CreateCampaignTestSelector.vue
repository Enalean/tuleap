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
            <translate>Tests</translate>
            <i class="fa fa-asterisk" aria-hidden="true"></i>
        </label>
        <select
            class="tlp-select"
            id="new-campaign-tests-selector"
            v-on:change="updateSelectedTests"
            v-model="selected_value"
            required
            data-test="new-campaign-tests"
        >
            <option value="none" v-translate>No tests</option>
            <option value="all" v-translate>All tests</option>
            <option value="milestone" v-translate="{ milestone_title }" selected>
                All tests in %{ milestone_title }
            </option>
            <optgroup
                v-bind:label="test_definitions_tracker_reports_group_label"
                v-if="display_test_definitions_tracker_reports_group_selector"
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
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { State } from "vuex-class";
import { TrackerReport } from "../../helpers/Campaigns/tracker-reports-retriever";
import { CampaignInitialTests } from "../../helpers/Campaigns/campaign-initial-tests";

function transformCampaignInitialTestToStringValue(initial_tests: CampaignInitialTests): string {
    if (initial_tests.test_selector === "report") {
        return initial_tests.report_id.toString();
    }
    return initial_tests.test_selector;
}

@Component
export default class CreateCampaignTestSelector extends Vue {
    @State
    readonly milestone_title!: string;

    @State
    readonly testdefinition_tracker_name!: string;

    @Prop({ required: true, default: { test_selector: "milestone" } })
    readonly value!: CampaignInitialTests;

    @Prop({ required: true })
    readonly testdefinition_tracker_reports!: TrackerReport[] | null;

    private selected_value = transformCampaignInitialTestToStringValue(this.value);

    get test_definitions_tracker_reports_group_label(): string {
        return this.$gettextInterpolate(
            this.$ngettext(
                "From %{ tracker_name } tracker report",
                "From %{ tracker_name } tracker reports",
                this.getNbTrackerReports()
            ),
            { tracker_name: this.testdefinition_tracker_name }
        );
    }

    get display_test_definitions_tracker_reports_group_selector(): boolean {
        return this.getNbTrackerReports() > 0;
    }

    private getNbTrackerReports(): number {
        return this.testdefinition_tracker_reports === null
            ? 0
            : this.testdefinition_tracker_reports.length;
    }

    public updateSelectedTests(): void {
        let initial_tests: CampaignInitialTests;
        if (
            this.selected_value === "none" ||
            this.selected_value === "all" ||
            this.selected_value === "milestone"
        ) {
            initial_tests = { test_selector: this.selected_value };
        } else {
            initial_tests = {
                test_selector: "report",
                report_id: Number.parseInt(this.selected_value, 10),
            };
        }
        this.$emit("input", initial_tests);
    }
}
</script>
