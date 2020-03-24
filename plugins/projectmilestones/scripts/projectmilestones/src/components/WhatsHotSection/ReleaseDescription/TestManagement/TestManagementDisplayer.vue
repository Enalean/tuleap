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
        <h2 class="tlp-pane-subtitle" v-translate>Tests Results</h2>
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
            {{ message_error_rest }}
        </div>
        <test-management v-else v-bind:release_data="release_data" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { MilestoneData, TestManagementCampaign } from "../../../../type";
import { Action, State } from "vuex-class";
import { FetchWrapperError } from "tlp";
import { is_testmanagement_activated } from "../../../../helpers/test-management-helper";
import TestManagement from "./TestManagement.vue";

@Component({
    components: { TestManagement },
})
export default class TestManagementDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;
    @State
    readonly project_milestone_activate_ttm!: boolean;
    @Action
    getTestManagementCampaigns!: (release_data: MilestoneData) => Promise<TestManagementCampaign>;

    is_loading = true;
    message_error_rest: string | null = null;

    get has_rest_error(): boolean {
        return this.message_error_rest !== null;
    }

    async created(): Promise<void> {
        if (!this.release_data.campaign) {
            try {
                this.release_data.campaign = await this.getTestManagementCampaigns(
                    this.release_data
                );
            } catch (rest_error) {
                await this.handle_error(rest_error);
            } finally {
                this.is_loading = false;
            }
        } else {
            this.is_loading = false;
        }
    }

    async handle_error(rest_error: FetchWrapperError): Promise<void> {
        try {
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + " " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
            throw error;
        }
    }

    get is_testmanagement_available(): boolean {
        return is_testmanagement_activated(this.release_data);
    }
}
</script>
