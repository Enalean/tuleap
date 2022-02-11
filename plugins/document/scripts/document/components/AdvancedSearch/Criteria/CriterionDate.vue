<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
        <label class="tlp-label" v-bind:for="id">{{ label }}</label>
        <div class="tlp-form-element tlp-form-element-prepend document-search-criterion">
            <select class="tlp-prepend" ref="operator" v-on:change="onChange">
                <option value=">" v-bind:selected="'>' === operator" v-translate>After</option>
                <option value="<" v-bind:selected="'<' === operator" v-translate>Before</option>
                <option value="=" v-bind:selected="'=' === operator" data-test="equal" v-translate>
                    On
                </option>
            </select>
            <input
                type="text"
                class="tlp-input"
                v-bind:id="id"
                v-bind:value="date"
                ref="date"
                v-on:input="onChange"
                v-bind:data-test="id"
                placeholder="YYYY-mm-dd"
                pattern="\d{4}-(?:02-(?:0[1-9]|[12][0-9])|(?:0[469]|11)-(?:0[1-9]|[12][0-9]|30)|(?:0[13578]|1[02])-(?:0[1-9]|[12][0-9]|3[01]))"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Component from "vue-class-component";
import Vue from "vue";
import { Prop, Ref } from "vue-property-decorator";
import type { AllowedSearchDateOperator, SearchDate } from "../../../type";

@Component
export default class CriterionText extends Vue {
    @Prop({ required: true })
    readonly name!: string;

    @Prop({ required: true })
    readonly value!: SearchDate | null;

    @Prop({ required: true })
    readonly label!: string;

    @Ref("operator")
    readonly operator_selector!: HTMLSelectElement;

    @Ref("date")
    readonly date_input!: HTMLInputElement;

    get id(): string {
        return "document-criterion-date-" + this.name;
    }

    get date(): string {
        return this.value?.date ?? "";
    }

    get operator(): AllowedSearchDateOperator {
        return this.value?.operator ?? ">";
    }

    onChange(): void {
        const new_value: SearchDate = {
            operator: this.operator_selector.value,
            date: this.date_input.value,
        };

        this.$emit("input", new_value);
    }
}
</script>
