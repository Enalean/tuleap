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
    <a
        v-bind:href="route_to_campaign_execution"
        class="tlp-pane test-plan-campaign"
        v-bind:class="classname"
        data-test="campaign"
    >
        <div class="tlp-pane-container">
            <div class="tlp-pane-header test-plan-campaign-header">
                <h1 class="tlp-pane-title">{{ campaign.label }}</h1>
                <div class="test-plan-campaign-header-stats">
                    <span class="test-plan-campaign-header-stats-info">
                        <i class="fa fa-check" aria-hidden="true"></i>
                        {{ nb_tests_title }}
                    </span>
                </div>
            </div>
            <div class="tlp-pane-section">
                <campaign-progression v-bind:campaign="campaign" />
            </div>
        </div>
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Campaign } from "../../type";
import CampaignProgression from "./CampaignProgression.vue";
import { State } from "vuex-class";

@Component({
    components: { CampaignProgression },
})
export default class CampaignCard extends Vue {
    @Prop({ required: true })
    readonly campaign!: Campaign;

    @State
    readonly project_id!: number;

    @State
    readonly milestone_id!: number;

    get nb_tests(): number {
        return (
            this.campaign.nb_of_blocked +
            this.campaign.nb_of_failed +
            this.campaign.nb_of_notrun +
            this.campaign.nb_of_passed
        );
    }

    get nb_tests_title(): string {
        return this.$gettextInterpolate(
            this.$ngettext("%{ nb } test", "%{ nb } tests", this.nb_tests),
            {
                nb: this.nb_tests,
            }
        );
    }

    get route_to_campaign_execution(): string {
        const url = new URL("/plugins/testmanagement/", window.location.href);
        url.searchParams.set("group_id", String(this.project_id));
        url.searchParams.set("milestone_id", String(this.milestone_id));
        url.hash = "#!/campaigns/" + this.campaign.id;

        return url.toString();
    }

    get classname(): string {
        if (this.campaign.is_error) {
            return "test-plan-campaign-is-error";
        }

        if (this.campaign.is_being_refreshed) {
            return "test-plan-campaign-is-being-refreshed";
        }

        if (this.campaign.is_just_refreshed) {
            return "test-plan-campaign-is-just-refreshed";
        }

        return "";
    }
}
</script>
