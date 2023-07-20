<!--
  - Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
    <div class="cross-tracker-expert-content">
        <div class="cross-tracker-expert-content-query tlp-form-element">
            <label class="tlp-label" for="expert-query-textarea" v-translate>Query</label>
            <textarea
                ref="query_textarea"
                type="text"
                class="cross-tracker-expert-content-query-textarea tlp-textarea"
                name="expert_query"
                id="expert-query-textarea"
                v-bind:placeholder="placeholder"
                v-model="value"
                data-test="expert-query-textarea"
            ></textarea>
            <p class="tlp-text-info">
                <i class="fa fa-info-circle"></i>
                <translate>
                    You can use: AND, OR, parenthesis. Autocomplete is activated with Ctrl + Space.
                </translate>
            </p>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="expert-query-allowed-fields" v-translate>
                Allowed fields
            </label>
            <select
                class="cross-tracker-expert-content-query-allowed-fields tlp-select"
                name="allowed-fields"
                id="expert-query-allowed-fields"
                multiple
                v-on:click.prevent="insertSelectedField"
            >
                <option value="@title">{{ title_semantic_label }}</option>
                <option value="@description">{{ description_semantic_label }}</option>
                <option value="@status">{{ status_semantic_label }}</option>
                <option value="@submitted_on">{{ submitted_on_label }}</option>
                <option value="@last_update_date">{{ lud_label }}</option>
                <option value="@submitted_by">{{ submitted_by_label }}</option>
                <option value="@last_update_by">{{ luby_label }}</option>
                <option value="@assigned_to">{{ assigned_to_label }}</option>
            </select>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import Component from "vue-class-component";
import { Prop } from "vue-property-decorator";
import {
    TQL_cross_tracker_autocomplete_keywords,
    TQL_cross_tracker_mode_definition,
} from "./tql-configuration";
import type { TQLCodeMirrorEditor } from "@tuleap/plugin-tracker-tql-codemirror";
import {
    insertAllowedFieldInCodeMirror,
    initializeTQLMode,
    codeMirrorify,
} from "@tuleap/plugin-tracker-tql-codemirror";
import type WritingCrossTrackerReport from "./writing-cross-tracker-report";

@Component({})
export default class QueryEditor extends Vue {
    @Prop({ required: true })
    readonly writingCrossTrackerReport!: WritingCrossTrackerReport;

    private code_mirror_instance: null | TQLCodeMirrorEditor = null;

    value: string = this.writingCrossTrackerReport.expert_query;

    created(): void {
        initializeTQLMode(TQL_cross_tracker_mode_definition);
    }
    mounted() {
        const submitFormCallback = () => {
            this.search();
        };

        const textarea_element = this.$refs.query_textarea;
        if (!(textarea_element instanceof HTMLTextAreaElement)) {
            throw new Error("Textarea not found in DOM");
        }

        this.code_mirror_instance = codeMirrorify(
            textarea_element,
            TQL_cross_tracker_autocomplete_keywords,
            submitFormCallback
        );

        if (!this.code_mirror_instance) {
            throw new Error("Code mirror is not accessible");
        }
        this.code_mirror_instance.on("change", () => {
            if (!this.code_mirror_instance) {
                throw new Error("Code mirror is not accessible");
            }
            this.writingCrossTrackerReport.expert_query = this.code_mirror_instance.getValue();
        });
    }

    get title_semantic_label(): string {
        return this.$gettext("Title");
    }
    get description_semantic_label(): string {
        return this.$gettext("Description");
    }
    get status_semantic_label(): string {
        return this.$gettext("Status");
    }
    get submitted_on_label(): string {
        return this.$gettext("Submitted on");
    }
    get lud_label(): string {
        return this.$gettext("Last update date");
    }
    get submitted_by_label(): string {
        return this.$gettext("Submitted by");
    }
    get luby_label(): string {
        return this.$gettext("Last update by");
    }
    get assigned_to_label(): string {
        return this.$gettext("Assigned to");
    }
    get placeholder(): string {
        return this.$gettext("Example: @title = 'value'");
    }

    insertSelectedField(event: Event): void {
        if (!this.code_mirror_instance) {
            throw new Error("Code mirror is not accessible for adding field");
        }
        insertAllowedFieldInCodeMirror(event, this.code_mirror_instance);
    }

    search(): void {
        this.$emit("trigger-search");
    }
}
</script>
