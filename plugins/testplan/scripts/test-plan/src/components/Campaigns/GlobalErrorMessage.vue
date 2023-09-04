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
    <div v-if="has_refreshing_error" class="tlp-alert-danger test-plan-list-of-campaigns-error">
        {{ $gettext("An error occurred while retrieving information of the new campaign.") }}
    </div>
</template>
<script setup lang="ts">
import { useNamespacedState } from "vuex-composition-helpers";
import type { CampaignState } from "../../store/campaign/type";

const { has_refreshing_error } = useNamespacedState<Pick<CampaignState, "has_refreshing_error">>(
    "campaign",
    ["has_refreshing_error"],
);
</script>
