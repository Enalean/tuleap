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
        <list-of-campaigns-header v-bind:show-create-modal="showCreateModal" />
        <global-error-message />
        <campaign-card
            v-for="campaign of campaigns"
            v-bind:key="campaign.id"
            v-bind:campaign="campaign"
        />
        <campaign-skeleton v-if="is_loading" />
        <campaign-empty-state
            v-if="should_empty_state_be_displayed"
            v-bind:show-create-modal="showCreateModal"
        />
        <campaign-error-state v-if="should_error_state_be_displayed" />
        <create-modal v-bind:is="show_create_modal" />
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import CampaignSkeleton from "./CampaignSkeleton.vue";
import CampaignCard from "./CampaignCard.vue";
import { Campaign } from "../../type";
import ListOfCampaignsHeader from "./ListOfCampaignsHeader.vue";
import GlobalErrorMessage from "./GlobalErrorMessage.vue";

const campaign = namespace("campaign");

@Component({
    components: {
        GlobalErrorMessage,
        ListOfCampaignsHeader,
        CampaignCard,
        CampaignSkeleton,
        "campaign-empty-state": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-campaigns-emptystate" */ "./CampaignEmptyState.vue"
            ),
        "campaign-error-state": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "testplan-campaigns-errorstate" */ "./CampaignErrorState.vue"
            ),
    },
})
export default class ListOfCampaigns extends Vue {
    @campaign.State
    readonly is_loading!: boolean;

    @campaign.State
    readonly has_loading_error!: boolean;

    @campaign.State
    readonly campaigns!: Campaign[];

    @campaign.Action
    loadCampaigns!: () => void;

    private show_create_modal: (() => Promise<Record<string, unknown>>) | string = "";

    created(): void {
        this.loadCampaigns();
    }

    showCreateModal(): void {
        this.show_create_modal = (): Promise<Record<string, unknown>> =>
            import(/* webpackChunkName: "testplan-create-campaign-modal" */ "./CreateModal.vue");
    }

    get should_empty_state_be_displayed(): boolean {
        return this.campaigns.length === 0 && !this.is_loading && !this.has_loading_error;
    }

    get should_error_state_be_displayed(): boolean {
        return this.has_loading_error;
    }
}
</script>
