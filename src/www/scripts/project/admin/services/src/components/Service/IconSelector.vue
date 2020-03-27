<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="id">
            <translate>Icon</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <select
            class="tlp-select"
            v-bind:id="id"
            name="icon_name"
            required
            ref="select"
            style="width: 100%;"
        >
            <option
                v-for="(label, icon_name) in allowed_icons"
                v-bind:key="icon_name"
                v-bind:value="icon_name"
            >
                {{ label }}
            </option>
        </select>
    </div>
</template>
<script>
import { select2 } from "tlp";

export default {
    name: "IconSelector",
    props: {
        id: {
            type: String,
            required: true,
        },
        value: {
            type: String,
            required: true,
        },
        allowed_icons: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            selector: null,
        };
    },
    watch: {
        value(value) {
            this.selector.val(value).trigger("change");
        },
    },
    mounted() {
        this.selector = select2(this.$refs.select, {
            placeholder: this.$gettext("Choose an icon"),
            allowClear: true,
            templateSelection: this.formatItem,
            templateResult: this.formatItem,
        })
            .val(this.value)
            .trigger("change")
            .on("change", this.onChange);
    },
    beforeDestroy() {
        this.selector.off().select2("destroy");
    },
    methods: {
        onChange() {
            this.$emit("input", this.selector.val());
        },
        formatItem(item) {
            if (item.id === "") {
                return item.text;
            }

            const icon = document.createElement("i");
            icon.classList.add("fa", "fa-fw", "project-admin-services-modal-icon-item", item.id);
            const span = document.createElement("span");
            span.insertAdjacentElement("afterbegin", icon);
            span.insertAdjacentText("beforeend", item.text);
            return span;
        },
    },
};
</script>
