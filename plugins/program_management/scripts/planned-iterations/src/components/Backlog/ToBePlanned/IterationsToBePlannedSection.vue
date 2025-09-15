<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div class="unplanned-iterations">
        <h2 class="planned-iterations-section-title">
            {{ $gettext("To be planned by the Teams") }}
        </h2>
        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
        <div
            v-if="has_error"
            class="tlp-alert-danger"
            data-test="unplanned-elements-retrieval-error-message"
        >
            {{ error_message }}
        </div>
        <div
            v-if="!is_loading && !has_error && user_stories.length === 0"
            class="empty-state-page"
            data-test="no-unplanned-elements-empty-state"
        >
            <p class="empty-state-text">
                {{ $gettext("There is no unplanned element") }}
            </p>
        </div>
        <user-story-card
            v-for="user_story in user_stories"
            v-bind:key="user_story.id"
            v-bind:user_story="user_story"
        />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import { retrieveUnplannedElements } from "../../../helpers/increment-unplanned-elements-retriever";
import UserStoryCard from "../Iteration/UserStoryCard.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import type { UserStory } from "../../../type";
import type { ProgramIncrement } from "../../../store/configuration";

const { $gettext } = useGettext();

const { program_increment } = useNamespacedState<{
    program_increment: ProgramIncrement;
}>("configuration", ["program_increment"]);

const user_stories = ref<ReadonlyArray<UserStory>>([]);
const is_loading = ref(false);
const has_error = ref(false);
const error_message = ref("");

onMounted(async () => {
    try {
        is_loading.value = true;
        user_stories.value = await retrieveUnplannedElements(program_increment.value.id);
    } catch (_e) {
        has_error.value = true;
        error_message.value = $gettext("An error occurred loading unplanned elements");
    } finally {
        is_loading.value = false;
    }
});
</script>
