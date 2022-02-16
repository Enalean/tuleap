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
        <label class="tlp-label" v-bind:for="id">{{ criterion.label }}</label>
        <div class="tlp-form-element tlp-form-element-prepend document-search-criterion">
            <select class="tlp-prepend" v-on:change="onChangeOperator($event.target.value)">
                <option value=">" v-bind:selected="'>' === operator" v-translate>After</option>
                <option value="<" v-bind:selected="'<' === operator" v-translate>Before</option>
                <option value="=" v-bind:selected="'=' === operator" data-test="equal" v-translate>
                    On
                </option>
            </select>
            <date-flat-picker
                v-bind:id="id"
                required="false"
                v-bind:value="date"
                v-on:input="onChangeDate"
                v-bind:data-test="id"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Component from "vue-class-component";
import Vue from "vue";
import { Prop, Watch } from "vue-property-decorator";
import type { AllowedSearchDateOperator, SearchCriterionDate, SearchDate } from "../../../type";
import DateFlatPicker from "../../Folder/Metadata/DateFlatPicker.vue";

@Component({
    components: { DateFlatPicker },
})
export default class CriterionDate extends Vue {
    @Prop({ required: true })
    readonly criterion!: SearchCriterionDate;

    @Prop({ required: true })
    readonly value!: SearchDate | null;

    private date = "";
    private operator: AllowedSearchDateOperator = ">";

    @Watch("value", { immediate: true })
    initializeValue(value: SearchDate | null): void {
        this.date = value?.date ?? "";
        this.operator = value?.operator ?? ">";
    }

    get id(): string {
        return "document-criterion-date-" + this.criterion.name;
    }

    onChangeOperator(new_operator: AllowedSearchDateOperator): void {
        this.onChange(new_operator, this.date);
    }

    onChangeDate(new_date: string): void {
        this.onChange(this.operator, new_date);
    }

    onChange(operator: AllowedSearchDateOperator, date: string): void {
        const new_value: SearchDate = { operator, date };

        this.$emit("input", new_value);
    }
}
</script>
