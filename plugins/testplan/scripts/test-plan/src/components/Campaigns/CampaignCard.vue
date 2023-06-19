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
<script setup lang="ts">
import { computed } from "vue";
import type { Campaign } from "../../type";
import { useGettext } from "vue3-gettext";
import { useState } from "vuex-composition-helpers";
import CampaignProgression from "./CampaignProgression.vue";

const props = defineProps<{
    campaign: Campaign;
}>();

const { project_id, milestone_id } = useState<{ project_id: number; milestone_id: number }>([
    "project_id",
    "milestone_id",
]);

const { interpolate, $ngettext } = useGettext();

const nb_tests_title = computed((): string => {
    const nb_tests =
        props.campaign.nb_of_blocked +
        props.campaign.nb_of_failed +
        props.campaign.nb_of_notrun +
        props.campaign.nb_of_passed;

    return interpolate($ngettext("%{ nb } test", "%{ nb } tests", nb_tests), {
        nb: nb_tests,
    });
});

const route_to_campaign_execution = computed((): string => {
    const url = new URL("/plugins/testmanagement/", window.location.href);
    url.searchParams.set("group_id", String(project_id.value));
    url.searchParams.set("milestone_id", String(milestone_id.value));
    url.hash = "#!/campaigns/" + props.campaign.id;

    return url.toString();
});

const classname = computed((): string => {
    if (props.campaign.is_error) {
        return "test-plan-campaign-is-error";
    }

    if (props.campaign.is_being_refreshed) {
        return "test-plan-campaign-is-being-refreshed";
    }

    if (props.campaign.is_just_refreshed) {
        return "test-plan-campaign-is-just-refreshed";
    }

    return "";
});
</script>
