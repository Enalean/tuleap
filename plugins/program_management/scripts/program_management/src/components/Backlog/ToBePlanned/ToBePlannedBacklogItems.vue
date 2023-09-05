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
                v-else
                v-for="user_story in user_stories"
                v-bind:key="user_story.id"
                v-bind:user_story="user_story"
            />
        </div>
        <div
            class="backlog-items-children-container-handle"
            ref="openCloseButton"
            data-test="backlog-items-open-close-button"
        >
            <i
                class="fa fa-fw backlog-items-children-container-handle-icon"
                v-bind:class="{ 'fa-chevron-down': !is_opened, 'fa-chevron-up': is_opened }"
            ></i>
        </div>
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import Vue from "vue";
import type { UserStory } from "../../../helpers/UserStories/user-stories-retriever";
import BacklogElementSkeleton from "../BacklogElementSkeleton.vue";
import type { Feature } from "../../../type";
import { handleError } from "../../../helpers/error-handler";
import ErrorDisplayer from "../ErrorDisplayer.vue";
import UserStoryDisplayer from "../UserStoryDisplayer.vue";

@Component({
    components: { UserStoryDisplayer, ErrorDisplayer, BacklogElementSkeleton },
})
export default class ToBePlannedBacklogItems extends Vue {
    @Prop({ required: true })
    readonly to_be_planned_element!: Feature;

    user_stories: UserStory[] = [];
    is_loading_user_story = false;
    message_error_rest = "";
    is_opened = false;

    mounted(): void {
        const button_close_stories = this.$refs.openCloseButton;

        if (!(button_close_stories instanceof HTMLElement)) {
            throw Error("No openCloseButton in component");
        }

        button_close_stories.addEventListener("click", async () => {
            this.is_opened = !this.is_opened;
            if (this.is_opened && this.user_stories.length === 0) {
                await this.loadUserStories();
            }
        });
    }

    async loadUserStories(): Promise<void> {
        if (this.to_be_planned_element.user_stories) {
            this.user_stories = this.to_be_planned_element.user_stories;
            return;
        }

        try {
            this.is_loading_user_story = true;
            this.user_stories = await this.$store.dispatch(
                "linkUserStoriesToBePlannedElements",
                this.to_be_planned_element.id,
            );
        } catch (rest_error) {
            this.message_error_rest = await handleError(rest_error, this);
            throw rest_error;
        } finally {
            this.is_loading_user_story = false;
        }
    }
}
</script>
