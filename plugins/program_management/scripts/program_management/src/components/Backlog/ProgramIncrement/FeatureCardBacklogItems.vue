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
        <div class="backlog-items-children-container">
            <program-increment-skeleton v-if="is_loading_user_story" />
            <backlog-items-error-show
                v-else-if="message_error_rest.length > 0"
                v-bind:message_error_rest="message_error_rest"
            />
            <user-story-displayer
                v-else
                v-for="user_story in user_stories"
                v-bind:key="user_story.id"
                v-bind:user_story="user_story"
            />
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { Feature } from "../../../helpers/ProgramIncrement/Feature/feature-retriever";
import ProgramIncrementSkeleton from "./ProgramIncrementSkeleton.vue";
import type { ProgramIncrement } from "../../../helpers/ProgramIncrement/program-increment-retriever";
import { Mutation } from "vuex-class";
import type { UserStory } from "../../../helpers/BacklogItems/children-feature-retriever";
import { getLinkedUserStoriesToFeature } from "../../../helpers/BacklogItems/children-feature-retriever";
import type { LinkUserStoryToFeature } from "../../../store/mutations";
import { handleError } from "../../../helpers/error-handler";
import BacklogItemsErrorShow from "../BacklogItemsErrorShow.vue";
import UserStoryDisplayer from "../UserStoryDisplayer.vue";

@Component({
    components: { UserStoryDisplayer, BacklogItemsErrorShow, ProgramIncrementSkeleton },
})
export default class FeatureCardBacklogItems extends Vue {
    @Prop({ required: true })
    readonly feature!: Feature;

    @Prop({ required: true })
    readonly program_increment!: ProgramIncrement;

    @Mutation
    readonly linkUserStoriesToFeature!: (user_story_feature: LinkUserStoryToFeature) => void;

    private user_stories: UserStory[] = [];
    private is_loading_user_story = false;
    private message_error_rest = "";

    async mounted(): Promise<void> {
        if (this.feature.user_stories) {
            this.user_stories = this.feature.user_stories;
            return;
        }

        try {
            this.is_loading_user_story = true;
            this.user_stories = await getLinkedUserStoriesToFeature(this.feature.artifact_id);
            this.linkUserStoriesToFeature({
                user_stories: this.user_stories,
                element_id: this.feature.artifact_id,
                program_increment: this.program_increment,
            });
        } catch (rest_error) {
            this.message_error_rest = await handleError(rest_error, this);
            throw rest_error;
        } finally {
            this.is_loading_user_story = false;
        }
    }
}
</script>
