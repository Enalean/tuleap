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
    <div v-if="is_testmanagement_available" class="container-chart-ttm">
        <div v-if="is_loading" class="release-loader" data-test="loading-data"></div>
        <div v-else-if="has_rest_error || are_some_tests_to_display" class="release-ttm-section">
            <h2 class="tlp-pane-subtitle">{{ $gettext("Tests Results") }}</h2>
            <div v-if="has_rest_error" class="tlp-alert-danger" data-test="error-rest">
                {{ message_error_rest }}
            </div>
            <test-management
                v-else-if="are_some_tests_to_display"
                v-bind:release_data="release_data"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { MilestoneData, TestManagementCampaign } from "../../../../type";
import { Action, State } from "vuex-class";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { is_testplan_activated } from "../../../../helpers/test-management-helper";
import TestManagement from "./TestManagement.vue";

@Component({
    components: { TestManagement },
})
export default class TestManagementDisplayer extends Vue {
    @Prop()
    readonly release_data!: MilestoneData;
    @State
    readonly project_id!: number;
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

    async handle_error(rest_error: unknown): Promise<void> {
        try {
            if (!(rest_error instanceof FetchWrapperError)) {
                throw rest_error;
            }
            const { error } = await rest_error.response.json();
            this.message_error_rest = error.code + " " + error.message;
        } catch (error) {
            this.message_error_rest = this.$gettext("Oops, an error occurred!");
            throw error;
        }
    }

    get is_testmanagement_available(): boolean {
        return is_testplan_activated(this.release_data);
    }

    get are_some_tests_to_display(): boolean {
        if (!this.release_data.campaign) {
            return false;
        }

        if (
            this.release_data.campaign.nb_of_notrun > 0 ||
            this.release_data.campaign.nb_of_failed > 0 ||
            this.release_data.campaign.nb_of_passed > 0 ||
            this.release_data.campaign.nb_of_blocked > 0
        ) {
            this.$emit("ttm-exists");
            return true;
        }

        return false;
    }
}
</script>
