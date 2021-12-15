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
    <div class="planned-iteration-content-items" data-test="iteration-user-story-list">
        <backlog-element-skeleton v-if="is_loading" data-test="to-be-planned-skeleton" />
        <iteration-no-content
            v-if="!has_user_stories && !is_loading && !has_error"
            data-test="empty-state"
        />
        <div v-if="has_error" class="tlp-alert-danger" data-test="iteration-content-error-message">
            {{ error_message }}
        </div>
        <user-story-card
            v-else
            v-for="user_story in user_stories"
            v-bind:key="user_story.id"
            v-bind:user_story="user_story"
            v-bind:iteration="iteration"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { retrieveIterationContent } from "../../../helpers/iteration-content-retriever";

import IterationNoContent from "./IterationNoContent.vue";
import UserStoryCard from "./UserStoryCard.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";

import type { UserStory, Iteration } from "../../../type";

@Component({
    components: {
        IterationNoContent,
        UserStoryCard,
        BacklogElementSkeleton,
    },
})
export default class IterationUserStoryList extends Vue {
    @Prop({ required: true })
    readonly iteration!: Iteration;

    private user_stories: UserStory[] = [];
    private is_loading = false;
    private has_error = false;
    private error_message = "";

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.user_stories = await retrieveIterationContent(this.iteration.id);
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext("An error has occurred loading content");
        } finally {
            this.is_loading = false;
        }
    }

    get has_user_stories(): boolean {
        return this.user_stories.length > 0;
    }
}
</script>
