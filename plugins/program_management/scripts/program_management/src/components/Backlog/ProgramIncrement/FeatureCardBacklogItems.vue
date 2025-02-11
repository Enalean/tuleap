<!--
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -->

<template>
    <div class="backlog-items-container">
        <div class="backlog-items-children-container" v-if="is_opened">
            <backlog-element-skeleton v-if="is_loading_user_story" />
            <error-displayer
                v-else-if="message_error_rest.length > 0"
                v-bind:message_error_rest="message_error_rest"
            />
            <user-story-displayer
                v-for="user_story in user_stories"
                v-bind:key="user_story.id"
                v-bind:user_story="user_story"
            />
        </div>
        <div
            class="backlog-items-children-container-handle"
            v-on:click="toggle"
            data-test="backlog-items-open-close-button"
        >
            <i
                class="fa fa-fw backlog-items-children-container-handle-icon"
                v-bind:class="{ 'fa-chevron-down': !is_opened, 'fa-chevron-up': is_opened }"
            ></i>
        </div>
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { useActions } from "vuex-composition-helpers";
import { useGettext } from "vue3-gettext";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import type { UserStory } from "../../../helpers/UserStories/user-stories-retriever";
import { handleError } from "../../../helpers/error-handler";
import ErrorDisplayer from "../ErrorDisplayer.vue";
import UserStoryDisplayer from "../UserStoryDisplayer.vue";
import type { Feature } from "../../../type";

const { linkUserStoriesToFeature } = useActions(["linkUserStoriesToFeature"]);

const gettext_provider = useGettext();

const props = defineProps<{
    feature: Feature;
    program_increment: ProgramIncrement;
}>();

const user_stories = ref<UserStory[]>([]);
const is_loading_user_story = ref(false);
const message_error_rest = ref("");
const is_opened = ref(false);

function toggle(): void {
    is_opened.value = !is_opened.value;
    if (is_opened.value && user_stories.value.length === 0) {
        loadUserStories();
    }
}

async function loadUserStories(): Promise<void> {
    if (props.feature.user_stories) {
        user_stories.value = props.feature.user_stories;
        return;
    }

    try {
        is_loading_user_story.value = true;
        user_stories.value = await linkUserStoriesToFeature({
            artifact_id: props.feature.id,
            program_increment: props.program_increment,
        });
    } catch (rest_error) {
        if (rest_error instanceof FetchWrapperError) {
            message_error_rest.value = await handleError(rest_error, gettext_provider);
        }
        throw rest_error;
    } finally {
        is_loading_user_story.value = false;
    }
}

defineExpose({ message_error_rest });
</script>
