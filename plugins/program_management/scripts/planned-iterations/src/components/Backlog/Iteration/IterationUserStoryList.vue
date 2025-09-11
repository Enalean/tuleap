<!--
  - Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
    <iteration-no-content
        v-if="!has_user_stories && !is_loading && !has_error"
        data-test="empty-state"
    />
    <div v-if="has_error" class="tlp-alert-danger" data-test="iteration-content-error-message">
        {{ error_message }}
    </div>
    <user-story-card
        v-for="user_story in user_stories"
        v-bind:key="user_story.id"
        v-bind:user_story="user_story"
    />
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useActions, useGetters } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import IterationNoContent from "./IterationNoContent.vue";
import UserStoryCard from "./UserStoryCard.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";
import type { Iteration, UserStory } from "../../../type";

const { $gettext } = useGettext();

const props = defineProps<{ iteration: Iteration }>();

const { hasIterationContentInStore, getIterationContentFromStore } = useGetters<{
    hasIterationContentInStore: () => (iteration: Iteration) => boolean;
    getIterationContentFromStore: () => (iteration: Iteration) => ReadonlyArray<UserStory>;
}>(["hasIterationContentInStore", "getIterationContentFromStore"]);

const { fetchIterationContent } = useActions(["fetchIterationContent"]);

const user_stories = ref<ReadonlyArray<UserStory>>([]);
const is_loading = ref(false);
const has_error = ref(false);
const error_message = ref("");
const has_user_stories = computed((): boolean => user_stories.value.length > 0);

onMounted(async () => {
    if (hasIterationContentInStore.value(props.iteration)) {
        user_stories.value = getIterationContentFromStore.value(props.iteration);
        return;
    }

    try {
        is_loading.value = true;
        user_stories.value = await fetchIterationContent(props.iteration);
    } catch (_e) {
        has_error.value = true;
        error_message.value = $gettext("An error has occurred loading content");
    } finally {
        is_loading.value = false;
    }
});
</script>
