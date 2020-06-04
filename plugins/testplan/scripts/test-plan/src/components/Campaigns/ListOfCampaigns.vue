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
    <section class="test-plan-list-of-campaigns">
        <translate tag="h2" class="test-plan-list-of-campaigns-title">Test campaigns</translate>
        <campaign-card
            v-for="campaign of campaigns"
            v-bind:key="campaign.id"
            v-bind:campaign="campaign"
        />
        <campaign-skeleton v-if="is_loading" />
        <campaign-empty-state v-if="should_empty_state_be_displayed" />
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import CampaignSkeleton from "./CampaignSkeleton.vue";
import CampaignCard from "./CampaignCard.vue";
import { Campaign } from "../../type";

const campaign = namespace("campaign");

@Component({
    components: {
        CampaignCard,
        CampaignSkeleton,
        "campaign-empty-state": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-campaigns-emptystate" */ "./CampaignEmptyState.vue"
            ),
    },
})
export default class ListOfCampaigns extends Vue {
    @campaign.State
    readonly is_loading!: boolean;

    @campaign.State
    readonly campaigns!: Campaign[];

    @campaign.Action
    loadCampaigns!: () => void;

    created(): void {
        this.loadCampaigns();
    }

    get should_empty_state_be_displayed(): boolean {
        return this.campaigns.length === 0 && !this.is_loading;
    }
}
</script>
