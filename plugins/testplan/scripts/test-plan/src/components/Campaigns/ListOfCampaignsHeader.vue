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
    <div class="test-plan-list-of-campaigns-header">
        <translate tag="h2" class="test-plan-list-of-campaigns-title">Test campaigns</translate>
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-small test-plan-list-of-campaigns-new-button"
            v-if="should_button_be_displayed"
            data-test="new-campaign"
            v-on:click="showCreateModal"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            <translate>New campaign</translate>
        </button>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import { Campaign } from "../../type";

const campaign = namespace("campaign");

@Component
export default class ListOfCampaignsHeader extends Vue {
    @State
    readonly user_can_create_campaign!: boolean;

    @campaign.State
    readonly is_loading!: boolean;

    @campaign.State
    readonly has_loading_error!: boolean;

    @campaign.State
    readonly campaigns!: Campaign[];

    @Prop({ required: true })
    readonly showCreateModal!: () => void;

    get should_button_be_displayed(): boolean {
        return (
            !this.has_loading_error && this.campaigns.length > 0 && this.user_can_create_campaign
        );
    }
}
</script>
