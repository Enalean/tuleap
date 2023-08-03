<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        class="project-release"
        v-bind:class="{
            'project-release-toggle-closed': !is_open,
            'tlp-tooltip tlp-tooltip-top': is_loading,
        }"
        v-bind:data-tlp-tooltip="$gettext('Loading data...')"
    >
        <release-header
            v-on:toggle-release-details="toggleReleaseDetails()"
            v-bind:release_data="displayed_release"
            v-bind:is-loading="is_loading"
            v-bind:class="{ 'project-release-toggle-closed': !is_open, disabled: is_loading }"
            v-bind:is-past-release="isPastRelease"
        />
        <div v-if="is_open" data-test="toggle-open" class="release-toggle">
            <div v-if="has_error" class="tlp-alert-danger" data-test="show-error-message">
                {{ error_message }}
            </div>
            <div v-else data-test="display-release-data">
                <release-badges-displayer
                    v-bind:release_data="displayed_release"
                    v-bind:is-open="isOpen"
                    v-bind:is-past-release="isPastRelease"
                />
                <release-description v-bind:release_data="displayed_release" />
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import ReleaseBadgesDisplayer from "./ReleaseBadges/ReleaseBadgesDisplayer.vue";
import ReleaseDescription from "./ReleaseDescription/ReleaseDescription.vue";
import ReleaseHeader from "./ReleaseHeader/ReleaseHeader.vue";
import Vue from "vue";
import type { MilestoneData } from "../../type";
import { Component, Prop } from "vue-property-decorator";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { is_testplan_activated } from "../../helpers/test-management-helper";
import { useStore } from "../../stores/root";

@Component({
    components: {
        ReleaseHeader,
        ReleaseDescription,
        ReleaseBadgesDisplayer,
    },
})
export default class ReleaseDisplayer extends Vue {
    public root_store = useStore();

    @Prop()
    readonly release_data!: MilestoneData;
    @Prop()
    readonly isOpen!: boolean;
    @Prop()
    isPastRelease!: boolean;

    is_open = false;
    is_loading = true;
    error_message: string | null = null;
    release_data_enhanced: MilestoneData | null = null;

    get has_error(): boolean {
        return this.error_message !== null;
    }

    get displayed_release(): MilestoneData {
        return this.release_data_enhanced ? this.release_data_enhanced : this.release_data;
    }

    async created(): Promise<void> {
        try {
            this.release_data_enhanced = await this.root_store.getEnhancedMilestones(
                this.release_data,
            );
            this.is_open = this.isOpen;
            if (this.isPastRelease && this.is_testplan_activated) {
                this.release_data_enhanced.campaign =
                    await this.root_store.getTestManagementCampaigns(this.release_data_enhanced);
            }
        } catch (rest_error) {
            await this.handle_error(rest_error);
        } finally {
            this.is_loading = false;
        }
    }

    async handle_error(rest_error: unknown): Promise<void> {
        if (!(rest_error instanceof FetchWrapperError) || rest_error.response === undefined) {
            this.error_message = this.$gettext("Oops, an error occurred!");
            throw rest_error;
        }
        try {
            const { error } = await rest_error.response.json();
            this.error_message = error.code + " " + error.message;
        } catch (error) {
            this.error_message = this.$gettext("Oops, an error occurred!");
        }
    }

    toggleReleaseDetails(): void {
        if (!this.is_loading || this.is_open) {
            this.is_open = !this.is_open;
        }
    }

    get is_testplan_activated(): boolean {
        return is_testplan_activated(this.release_data);
    }
}
</script>
