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
    <div class="tlp-dropdown" v-if="can_add_button_be_displayed" v-on:click.stop>
        <div class="tlp-dropdown-split-button test-plan-add-test-dropdown">
            <a
                v-bind:href="add_button_href"
                class="tlp-button-primary tlp-button-outline tlp-button-small tlp-dropdown-split-button-main"
                data-test="add-test-button"
                v-translate
            >
                Create a new test
            </a>
            <button
                class="tlp-button-primary tlp-button-outline tlp-append tlp-dropdown-split-button-caret tlp-button-small"
                ref="dropdownTrigger"
            >
                <i class="fa fa-caret-down"></i>
            </button>
        </div>
        <div class="tlp-dropdown-menu tlp-dropdown-menu-left" role="menu" ref="dropdownMenu">
            <a v-bind:href="edit_backlog_item_href" class="tlp-dropdown-menu-item" role="menuitem">
                <i class="fas fa-fw fa-pencil-alt"></i>
                <translate
                    v-bind:translate-params="{
                        item_type: backlog_item.short_type,
                        item_id: backlog_item.id,
                    }"
                >
                    Edit %{ item_type } #%{ item_id }
                </translate>
            </a>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import { BacklogItem } from "../../type";
import { State } from "vuex-class";
import { createDropdown } from "tlp";
import {
    buildCreateNewTestDefinitionLink,
    buildEditBacklogItemLink,
} from "../../helpers/BacklogItems/url-builder";

@Component
export default class AddTestButtonWithAdditionalActionsMenu extends Vue {
    @Prop({ required: true })
    readonly backlog_item!: BacklogItem;

    @State
    readonly testdefinition_tracker_id!: number | null;

    @State
    readonly milestone_id!: number;

    $refs!: {
        dropdownTrigger: HTMLElement;
        dropdownMenu: HTMLElement;
    };

    get add_button_href(): string {
        if (!this.testdefinition_tracker_id) {
            return "";
        }

        return buildCreateNewTestDefinitionLink(
            this.testdefinition_tracker_id,
            this.milestone_id,
            this.backlog_item
        );
    }

    get edit_backlog_item_href(): string {
        return buildEditBacklogItemLink(this.milestone_id, this.backlog_item);
    }

    get can_add_button_be_displayed(): boolean {
        return (
            this.backlog_item.is_expanded &&
            this.backlog_item.can_add_a_test &&
            Boolean(this.testdefinition_tracker_id) &&
            !this.backlog_item.is_loading_test_definitions &&
            !this.backlog_item.has_test_definitions_loading_error
        );
    }

    @Watch("can_add_button_be_displayed")
    onButtonDisplay(): void {
        this.$nextTick(() => {
            if (!this.$refs.dropdownTrigger || !this.$refs.dropdownMenu) {
                return;
            }

            createDropdown(this.$refs.dropdownTrigger, { dropdown_menu: this.$refs.dropdownMenu });
        });
    }
}
</script>
