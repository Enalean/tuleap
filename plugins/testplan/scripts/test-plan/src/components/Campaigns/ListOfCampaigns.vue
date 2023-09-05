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
        <list-of-campaigns-header v-bind:show_create_modal="showCreateModal" />
        <global-error-message />
        <campaign-card
            v-for="campaign of campaigns"
            v-bind:key="campaign.id"
            v-bind:campaign="campaign"
        />
        <campaign-skeleton v-if="is_loading" />
        <campaign-empty-state
            v-if="should_empty_state_be_displayed"
            v-bind:show_create_modal="showCreateModal"
            data-test="async-empty-state"
        />
        <campaign-error-state
            v-if="should_error_state_be_displayed"
            data-test="async-error-state"
        />
        <component v-bind:is="show_create_modal" />
    </section>
</template>
<script setup lang="ts">
import CampaignSkeleton from "./CampaignSkeleton.vue";
import CampaignCard from "./CampaignCard.vue";
import ListOfCampaignsHeader from "./ListOfCampaignsHeader.vue";
import GlobalErrorMessage from "./GlobalErrorMessage.vue";
import { computed, defineAsyncComponent, onMounted, shallowRef } from "vue";
import { useNamespacedActions, useNamespacedState } from "vuex-composition-helpers";
import type { CampaignActions } from "../../store/campaign/campaign-actions";
import type { CampaignState } from "../../store/campaign/type";

const CampaignEmptyState = defineAsyncComponent(
    () =>
        import(/* webpackChunkName: "testplan-campaigns-emptystate" */ "./CampaignEmptyState.vue"),
);
const CampaignErrorState = defineAsyncComponent(
    () =>
        import(/* webpackChunkName: "testplan-campaigns-errorstate" */ "./CampaignErrorState.vue"),
);

const { is_loading, has_loading_error, campaigns } = useNamespacedState<
    Pick<CampaignState, "is_loading" | "has_loading_error" | "campaigns">
>("campaign", ["is_loading", "has_loading_error", "campaigns"]);

const show_create_modal = shallowRef<undefined | unknown>(undefined);

function showCreateModal(): void {
    show_create_modal.value = defineAsyncComponent(
        () => import(/* webpackChunkName: "testplan-create-campaign-modal" */ "./CreateModal.vue"),
    );
}

const { loadCampaigns } = useNamespacedActions<CampaignActions>("campaign", ["loadCampaigns"]);

loadCampaigns();

onMounted(() => {
    const new_dropdown_link = document.querySelector(
        "[data-shortcut-create-option][data-test-plan-create-new-campaign]",
    );
    if (new_dropdown_link instanceof HTMLAnchorElement) {
        new_dropdown_link.addEventListener("click", (event) => {
            event.preventDefault();
            showCreateModal();
        });
    }
});

const should_empty_state_be_displayed = computed((): boolean => {
    return campaigns.value.length === 0 && !is_loading.value && !has_loading_error.value;
});

const should_error_state_be_displayed = computed((): boolean => {
    return has_loading_error.value;
});
</script>
