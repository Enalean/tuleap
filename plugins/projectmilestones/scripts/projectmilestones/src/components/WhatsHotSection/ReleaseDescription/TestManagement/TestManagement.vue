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
  -->

<template>
    <div
        v-if="is_testmanagement_available && project_milestone_activate_ttm"
        class="release-ttm-section"
    >
        <ul>
            <li data-test="nb-test-passed">
                <translate
                    v-bind:translate-params="{
                        total_test_passed: release_data.campaign.nb_of_passed,
                    }"
                    v-bind:translate-n="release_data.campaign.nb_of_passed"
                    translate-plural="%{ total_test_passed } tests passed"
                >
                    %{total_test_passed} test passed
                </translate>
            </li>
            <li data-test="nb-test-failed">
                <translate
                    v-bind:translate-params="{
                        total_test_failed: release_data.campaign.nb_of_failed,
                    }"
                    v-bind:translate-n="release_data.campaign.nb_of_failed"
                    translate-plural="%{ total_test_failed } tests failed"
                >
                    %{total_test_failed} test failed
                </translate>
            </li>
            <li data-test="nb-test-notrun">
                <translate
                    v-bind:translate-params="{
                        total_test_notrun: release_data.campaign.nb_of_notrun,
                    }"
                    v-bind:translate-n="release_data.campaign.nb_of_notrun"
                    translate-plural="%{ total_test_notrun } tests not run"
                >
                    %{total_test_notrun} test not run
                </translate>
            </li>
            <li data-test="nb-test-blocked">
                <translate
                    v-bind:translate-params="{
                        total_test_blocked: release_data.campaign.nb_of_blocked,
                    }"
                    v-bind:translate-n="release_data.campaign.nb_of_blocked"
                    translate-plural="%{ total_test_blocked } tests blocked"
                >
                    %{total_test_blocked} test blocked
                </translate>
            </li>
        </ul>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData } from "../../../../type";
import { State } from "vuex-class";
import { is_testmanagement_activated } from "../../../../helpers/test-management-helper";

@Component({})
export default class TestManagement extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_milestone_activate_ttm!: boolean;

    get is_testmanagement_available(): boolean {
        return (
            is_testmanagement_activated(this.release_data) && this.release_data.campaign !== null
        );
    }
}
</script>
