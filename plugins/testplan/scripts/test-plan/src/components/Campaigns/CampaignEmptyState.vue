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
    <section class="empty-state-pane">
        <div class="empty-state-illustration">
            <campaign-empty-state-svg />
        </div>
        <p class="empty-state-text">
            <translate>There is no test campaign yet</translate>
        </p>
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline empty-state-action"
            v-if="user_can_create_campaign"
            data-test="new-campaign"
            v-on:click="show_create_modal"
        >
            <i class="fa fa-plus tlp-button-icon"></i>
            <translate>Create new campaign</translate>
        </button>
    </section>
</template>
<script setup lang="ts">
import CampaignEmptyStateSvg from "./CampaignEmptyStateSvg.vue";
import { useState } from "vuex-composition-helpers";
import type { State } from "../../store/type";

defineProps<{
    show_create_modal: () => void;
}>();

const { user_can_create_campaign } = useState<Pick<State, "user_can_create_campaign">>([
    "user_can_create_campaign",
]);
</script>
<script lang="ts">
import { defineComponent } from "vue";

export default defineComponent({});
</script>
