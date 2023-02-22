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
        <h2 class="planned-iterations-section-title" v-translate>To be planned by the Teams</h2>

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
            <p class="empty-state-text" v-translate>There is no unplanned element</p>
        </div>

        <user-story-card
            v-else
            v-for="user_story in user_stories"
            v-bind:key="user_story.id"
            v-bind:user_story="user_story"
        />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { retrieveUnplannedElements } from "../../../helpers/increment-unplanned-elements-retriever";

import UserStoryCard from "../Iteration/UserStoryCard.vue";
import BacklogElementSkeleton from "../../BacklogElementSkeleton.vue";

import type { UserStory } from "../../../type";
import type { ProgramIncrement } from "../../../store/configuration";

const configuration = namespace("configuration");

@Component({
    components: {
        UserStoryCard,
        BacklogElementSkeleton,
    },
})
export default class IterationsToBePlannedSection extends Vue {
    @configuration.State
    readonly program_increment!: ProgramIncrement;

    private user_stories: UserStory[] = [];
    private is_loading = false;
    private has_error = false;
    private error_message = "";

    async mounted(): Promise<void> {
        try {
            this.is_loading = true;
            this.user_stories = await retrieveUnplannedElements(this.program_increment.id);
        } catch (e) {
            this.has_error = true;
            this.error_message = this.$gettext("An error occurred loading unplanned elements");
        } finally {
            this.is_loading = false;
        }
    }
}
</script>
