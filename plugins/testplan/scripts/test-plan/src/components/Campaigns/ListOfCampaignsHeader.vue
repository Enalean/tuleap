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
        <h2 class="test-plan-list-of-campaigns-title">
            {{ $gettext("Test campaigns") }}
        </h2>
        <button
            v-if="should_button_be_displayed"
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-button-small test-plan-list-of-campaigns-new-button"
            data-test="new-campaign"
            v-on:click="show_create_modal"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            {{ $gettext("New campaign") }}
        </button>
    </div>
</template>
<script setup lang="ts">
import { useNamespacedState, useState } from "vuex-composition-helpers";
import type { Campaign } from "../../type";
import { computed } from "vue";

defineProps<{
    show_create_modal: () => void;
}>();

const { user_can_create_campaign } = useState<{ user_can_create_campaign: boolean }>([
    "user_can_create_campaign",
]);
const { has_loading_error, campaigns } = useNamespacedState<{
    has_loading_error: boolean;
    campaigns: Campaign[];
}>("campaign", ["has_loading_error", "campaigns"]);

const should_button_be_displayed = computed(
    (): boolean =>
        !has_loading_error.value && campaigns.value.length > 0 && user_can_create_campaign.value,
);
</script>
