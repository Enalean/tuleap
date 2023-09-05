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
    <select class="tlp-input" style="width: 100%">
        <option v-if="!is_multiple"></option>
    </select>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type {
    DataFormat,
    GroupedDataFormat,
    IdTextPair,
    LoadingData,
    Options,
    Select2Plugin,
} from "tlp";
import { select2 } from "tlp";
import $ from "jquery";
import DOMPurify from "dompurify";
import mustache from "mustache";
import type { UserForPeoplePicker } from "./type";

@Component
export default class PeoplePicker extends Vue {
    @Prop({ required: true })
    readonly is_multiple!: boolean;

    @Prop({ required: true })
    readonly users!: UserForPeoplePicker[];

    @Prop({ required: true })
    readonly value!: number[];

    select2_people_picker: Select2Plugin | null = null;

    mounted(): void {
        const placeholder = this.is_multiple
            ? { text: this.$gettext("John"), id: "0" }
            : this.$gettext("Please choose…");

        const configuration: Options = {
            allowClear: true,
            data: this.users,
            multiple: this.is_multiple,
            placeholder,
            escapeMarkup: DOMPurify.sanitize,
            templateResult: this.formatUser,
            templateSelection: this.formatUserWhenSelected,
        };

        this.select2_people_picker = select2(this.$el, configuration);

        $(this.$el).on("change", this.onChange).select2("open");
    }

    destroyed(): void {
        if (this.select2_people_picker !== null) {
            $(this.$el).off().select2("destroy");
        }
    }

    onChange(): void {
        let selected_ids: string[];
        const val: string | number | string[] | undefined = $(this.$el).val();
        if (!val) {
            selected_ids = [];
        } else if (typeof val === "string" || typeof val === "number") {
            selected_ids = [`${val}`];
        } else {
            selected_ids = val;
        }

        const selected_ids_as_number: number[] = selected_ids.map((id) => Number(id));

        this.$emit("input", selected_ids_as_number);
    }

    formatUser(user: DataFormat | GroupedDataFormat | LoadingData): string {
        if (!this.isForPeoplePicker(user)) {
            return "";
        }

        return mustache.render(
            `<div class="select2-result-user">
                <div class="tlp-avatar-mini select2-result-user__avatar">
                    <img src="{{ avatar_url }}">
                </div>
                {{ display_name }}
            </div>`,
            user,
        );
    }

    isForPeoplePicker(user: IdTextPair | DataFormat | GroupedDataFormat | LoadingData): boolean {
        return "avatar_url" in user;
    }

    formatUserWhenSelected(
        user: IdTextPair | LoadingData | DataFormat | GroupedDataFormat,
    ): string {
        if (!this.isForPeoplePicker(user)) {
            return user.text;
        }

        return mustache.render(
            `<div class="tlp-avatar-mini">
                <img src="{{ avatar_url }}">
            </div>
            {{ display_name }}`,
            user,
        );
    }
}
</script>
