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
            <i class="fas fa-asterisk" aria-hidden="true"></i>
        </label>
        <select
            class="tlp-select"
            v-bind:id="id"
            name="icon_name"
            required
            ref="select"
            v-on:change="$emit('input', $event.target.value)"
        >
            <option
                v-for="(icon_info, icon_id) in allowed_icons"
                v-bind:key="icon_id"
                v-bind:value="icon_id"
                v-bind:selected="value === icon_id"
            >
                {{ icon_info.description }}
            </option>
        </select>
    </div>
</template>
<script>
import { createListPicker } from "@tuleap/list-picker";

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
    mounted() {
        this.selector = createListPicker(this.$refs.select, {
            is_filterable: true,
            placeholder: this.$gettext("Choose an icon"),
            items_template_formatter: (html_processor, value_id) => {
                const icon_info = this.allowed_icons[value_id];

                const template = html_processor`
                    <i aria-hidden="true" class="project-admin-services-modal-icon-item fa-fw ${icon_info["fa-icon"]}"></i>
                    <span>${icon_info.description}</span>
                `;
                return template;
            },
        });
    },
    beforeDestroy() {
        if (this.selector !== null) {
            this.selector.destroy();
        }
    },
};
</script>
