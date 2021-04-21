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
  -
  -->

<template>
    <div>
        <no-data-to-show-empty-state v-if="should_display_empty_state" />
        <something-went-wrong-empty-state
            v-else-if="should_display_error_state"
            v-bind:message="error_message"
        />
        <gantt-board
            v-else-if="tasks.length"
            v-bind:tasks="tasks"
            v-bind:visible_natures="visible_natures"
        />
        <loading-state v-else-if="is_loading" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";
import type { FetchWrapperError } from "tlp";
import GanttBoard from "./Gantt/GanttBoard.vue";
import type { Task, NaturesLabels } from "../type";
import { retrieveAllTasks } from "../helpers/task-retriever";
import LoadingState from "./LoadingState.vue";

@Component({
    components: { LoadingState, GanttBoard, SomethingWentWrongEmptyState, NoDataToShowEmptyState },
})
export default class App extends Vue {
    @Prop({ required: true })
    readonly roadmap_id!: number;

    @Prop({ required: true })
    private readonly visible_natures!: NaturesLabels;

    private tasks: Task[] = [];
    private error_message = "";
    private should_display_error_state = false;
    private should_display_empty_state = false;
    private is_loading = true;

    mounted(): void {
        this.loadTasks();
    }

    async loadTasks(): Promise<void> {
        try {
            this.tasks = await retrieveAllTasks(this.roadmap_id);
            if (this.tasks.length === 0) {
                this.should_display_empty_state = true;
            }
        } catch (e) {
            if (this.isFetchWrapperError(e)) {
                await this.handleRestError(e);
            } else {
                throw e;
            }
        } finally {
            this.is_loading = false;
        }
    }

    private async handleRestError(rest_error: FetchWrapperError): Promise<void> {
        this.should_display_error_state = true;
        this.error_message = "";

        if (rest_error.response.status === 404 || rest_error.response.status === 403) {
            this.should_display_empty_state = true;
            this.should_display_error_state = false;

            return;
        }

        if (rest_error.response.status === 400) {
            try {
                this.error_message = await this.getMessageFromRestError(rest_error);
            } catch (error) {
                // no custom message if we are unable to parse the error response
                throw rest_error;
            }
        }
    }

    private async getMessageFromRestError(rest_error: FetchWrapperError): Promise<string> {
        let response = await rest_error.response.json();

        if (Object.prototype.hasOwnProperty.call(response, "error")) {
            if (Object.prototype.hasOwnProperty.call(response.error, "i18n_error_message")) {
                return response.error.i18n_error_message;
            }

            return response.error.message;
        }

        return "";
    }

    private isFetchWrapperError(error: Error): error is FetchWrapperError {
        return "response" in error;
    }
}
</script>
