<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" for="tracker-creation-field-color">
            <translate>Color</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <select
            ref="color-selector"
            class="tlp-select tracker-color-selector"
            id="tracker-creation-field-color"
            name="tracker-color"
            data-select2-id="tracker-creation-field-color"
            tabindex="-1"
            aria-hidden="true"
        ></select>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component, Ref, Watch } from "vue-property-decorator";
import { State } from "vuex-class";
import mustache from "mustache";
import { sanitize } from "dompurify";
import $ from "jquery";
import type { DataFormat, GroupedDataFormat, LoadingData, Select2Plugin } from "tlp";
import { select2 } from "tlp";
import type { TrackerToBeCreatedMandatoryData, DataForColorPicker } from "../../../../store/type";

@Component
export default class FieldTrackerColor extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    @State
    readonly default_tracker_color!: string;

    @State
    readonly color_picker_data!: DataForColorPicker[];

    @Ref("color-selector")
    readonly color_selector!: HTMLSelectElement;

    @Watch("tracker_to_be_created", { deep: true })
    updateSelectedColor(
        old_value: TrackerToBeCreatedMandatoryData,
        new_value: TrackerToBeCreatedMandatoryData
    ): void {
        if (old_value.color !== new_value.color) {
            this.selectColor();
        }
    }

    private select2_color: Select2Plugin | null = null;

    mounted() {
        this.select2_color = select2(this.color_selector, {
            data: this.color_picker_data,
            containerCssClass: "tracker-color-container",
            dropdownCssClass: "tracker-color-results",
            minimumResultsForSearch: Infinity,
            dropdownAutoWidth: true,
            escapeMarkup: sanitize,
            templateResult: this.formatOptionColor,
            templateSelection: this.formatOptionColor,
        });

        this.selectColor();
    }

    destroyed(): void {
        if (this.select2_color !== null) {
            $(this.color_selector).off().select2("destroy");
        }
    }

    formatOptionColor(result: DataFormat | GroupedDataFormat | LoadingData): string {
        if (!result.id) {
            return "";
        }

        return mustache.render("<span class={{ id }}></span>", result);
    }

    hasTrackerAValidColor(): boolean {
        return (
            this.color_picker_data.findIndex(
                (data: DataForColorPicker) => this.tracker_to_be_created.color === data.id
            ) !== -1
        );
    }

    selectColor(): void {
        if (this.hasTrackerAValidColor()) {
            $(this.color_selector).val(this.tracker_to_be_created.color);
        } else {
            $(this.color_selector).val(this.default_tracker_color);
        }

        $(this.color_selector).trigger("change");
    }
}
</script>
