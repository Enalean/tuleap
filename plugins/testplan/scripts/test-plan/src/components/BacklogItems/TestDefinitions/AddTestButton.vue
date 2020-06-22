<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <a
        v-bind:href="add_button_href"
        class="tlp-button-primary tlp-button-outline test-plan-add-test-button"
        v-bind:class="add_button_class"
        v-if="can_add_button_be_displayed"
        data-test="add-test-button"
        v-translate
    >
        Add a test
    </a>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { BacklogItem } from "../../../type";
import { State } from "vuex-class";

@Component
export default class ListOfTestDefinitions extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @Prop({ required: true })
    readonly should_empty_state_be_displayed!: boolean;

    @State
    readonly testdefinition_tracker_id!: number | null;

    @State
    readonly milestone_id!: number;

    @State
    readonly autoscroll_to_create_test_button_of_backlog_item_id!: number;

    mounted(): void {
        if (this.autoscroll_to_create_test_button_of_backlog_item_id === this.backlog_item.id) {
            this.autoscroll();
        }
    }

    autoscroll(): void {
        if (!document.body?.parentElement) {
            return;
        }

        const viewport_height = document.body.parentElement.clientHeight;
        const middle_of_the_screen = viewport_height / 2;
        const rect = this.$el.getBoundingClientRect();
        const current_top = rect.top;
        if (current_top > middle_of_the_screen) {
            setTimeout(() => {
                const rect = this.$el.getBoundingClientRect();
                const new_top = rect.top + rect.height / 2 - middle_of_the_screen;
                window.scrollTo({ top: new_top, behavior: "smooth" });
            }, 1000);
        }
    }

    get add_button_class(): string {
        if (this.should_empty_state_be_displayed) {
            return "test-plan-add-test-button-with-empty-state";
        }

        return "";
    }

    get add_button_href(): string {
        if (!this.testdefinition_tracker_id) {
            return "";
        }

        const url_params = new URLSearchParams({
            tracker: String(this.testdefinition_tracker_id),
            func: "new-artifact",
            ttm_backlog_item_id: String(this.backlog_item.id),
            ttm_milestone_id: String(this.milestone_id),
        });

        return `/plugins/tracker/?${url_params.toString()}`;
    }

    get can_add_button_be_displayed(): boolean {
        return (
            this.backlog_item.can_add_a_test &&
            Boolean(this.testdefinition_tracker_id) &&
            !this.backlog_item.is_loading_test_definitions &&
            !this.backlog_item.has_test_definitions_loading_error
        );
    }
}
</script>
