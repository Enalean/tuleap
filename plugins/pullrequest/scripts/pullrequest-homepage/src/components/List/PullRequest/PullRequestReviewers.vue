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
    <div class="pull-request-card-reviewers">
        <div class="pull-request-card-reviewers-overlap-container">
            <div
                v-for="reviewer in reviewers_to_display"
                v-bind:key="reviewer.id"
                class="tlp-avatar-medium pull-request-card-reviewer"
            >
                <img
                    v-bind:title="reviewer.display_name"
                    v-bind:src="reviewer.avatar_url"
                    class="media-object"
                    aria-hidden="true"
                    data-test="pull-request-card-reviewer-avatar"
                />
            </div>
            <div v-if="has_more_reviewers" class="tlp-dropdown">
                <div
                    class="pull-request-card-remaining-reviewers-count"
                    ref="dropdown_trigger"
                    data-test="pull-request-card-remaining-reviewer-count"
                >
                    +{{ nb_reviewers_not_displayed }}
                </div>
                <div
                    class="tlp-dropdown-menu pull-request-card-remaining-reviewers-dropdown"
                    role="menu"
                    ref="dropdown_content"
                >
                    <div
                        v-for="remaining_reviewer in remaining_reviewers"
                        v-bind:key="remaining_reviewer.id"
                        class="tlp-dropdown-menu-item"
                    >
                        <div class="tlp-avatar-mini">
                            <img
                                v-bind:src="remaining_reviewer.avatar_url"
                                class="media-object"
                                aria-hidden="true"
                                data-test="pull-request-card-remaining-reviewer-avatar"
                            />
                        </div>
                        <span class="pull-request-card-remaining-reviewer-name">{{
                            remaining_reviewer.display_name
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import type { Ref } from "vue";
import { createDropdown } from "@tuleap/tlp-dropdown";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

const props = defineProps<{
    pull_request: PullRequest;
}>();

const dropdown_trigger: Ref<HTMLElement | undefined> = ref();
const dropdown_content: Ref<HTMLElement | undefined> = ref();

const nb_max_displayed_reviewers = 3;
const reviewers_to_display = props.pull_request.reviewers.slice(0, nb_max_displayed_reviewers);
const remaining_reviewers = props.pull_request.reviewers.slice(nb_max_displayed_reviewers);
const has_more_reviewers = props.pull_request.reviewers.length > nb_max_displayed_reviewers;
const nb_reviewers_not_displayed = props.pull_request.reviewers.length - nb_max_displayed_reviewers;

const preventClickInDropdownToTriggerRedirection = (dropdown: HTMLElement) => {
    dropdown.addEventListener("click", (event) => event.preventDefault());
};

onMounted(() => {
    if (!dropdown_trigger.value || !dropdown_content.value) {
        return;
    }

    preventClickInDropdownToTriggerRedirection(dropdown_content.value);

    createDropdown(dropdown_trigger.value);
});
</script>

<style scoped lang="scss">
.pull-request-card-reviewers {
    display: flex;
    flex: 15% 0 0;
    align-items: flex-start;
    padding: 0 var(--tlp-medium-spacing);
}

.pull-request-card-reviewers-overlap-container {
    display: inline-flex;
    flex: 1;
    flex-flow: nowrap;
    align-items: center;
    justify-content: flex-end;
}

.pull-request-card-reviewer {
    $border-width: 4px;

    border: $border-width solid var(--tlp-white-color);

    &:not(:first-child) {
        margin-left: calc(-#{$border-width} * 2);
    }
}

.pull-request-card-remaining-reviewers-count {
    margin: 0 0 0 2px;
    color: var(--tlp-dimmed-color);

    &:hover {
        border-bottom: 1px solid var(--tlp-dimmed-color);
    }
}

.pull-request-card-remaining-reviewers-dropdown {
    transform: translate(-12px, 10px);
    cursor: default;

    > .tlp-dropdown-menu-item {
        display: flex;
        align-items: center;
        cursor: default;
    }
}

.pull-request-card-remaining-reviewer-name {
    margin: 0 0 0 var(--tlp-small-spacing);
}
</style>
