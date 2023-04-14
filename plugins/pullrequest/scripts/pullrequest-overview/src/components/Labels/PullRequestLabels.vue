<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <div class="tlp-property">
        <label class="tlp-label">
            {{ $gettext("Tags") }}
        </label>
        <div class="pull-request-labels">
            <span
                v-for="label in labels"
                v-bind:key="label.id"
                v-bind:class="getBadgeClasses(label)"
                data-test="pull-request-label"
            >
                <i class="fa-solid fa-tag tlp-badge-icon" aria-hidden="true"></i>
                {{ label.label }}
            </span>
            <span
                v-if="has_no_labels"
                class="pull-request-no-labels-empty-state-text"
                data-test="no-labels-yet-text"
            >
                {{ $gettext("No tags have been assigned yet") }}
            </span>
        </div>
        <property-skeleton v-if="are_labels_loading" />
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PullRequestLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchPullRequestLabels } from "../../api/tuleap-rest-querier";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import PropertySkeleton from "../ReadOnlyInfo/PropertySkeleton.vue";

const { $gettext } = useGettext();

const pull_request_id = parseInt(strictInject(PULL_REQUEST_ID_KEY), 10);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const labels = ref<ReadonlyArray<PullRequestLabel>>([]);
const are_labels_loading = ref(true);
const has_no_labels = computed(() => labels.value.length === 0 && !are_labels_loading.value);

const getBadgeClasses = (label: PullRequestLabel): string[] => {
    const classes = [`tlp-badge-${label.color}`];

    if (label.is_outline) {
        classes.push("tlp-badge-outline");
    }

    return classes;
};

fetchPullRequestLabels(pull_request_id)
    .match((result) => {
        labels.value = result;
    }, displayTuleapAPIFault)
    .finally(() => {
        are_labels_loading.value = false;
    });
</script>

<style lang="scss">
.pull-request-labels {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
}

.pull-request-no-labels-empty-state-text {
    color: var(--tlp-dark-color);
    font-style: italic;
}
</style>
