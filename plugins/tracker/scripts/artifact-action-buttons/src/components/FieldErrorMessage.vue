<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div>
        <ul class="move-artifact-display-more-fields-message">
            <li v-for="field in fields_to_display" v-bind:key="field.field_id">
                {{ field.label }}
            </li>
        </ul>
        <button
            v-on:click="is_minimal_display = false"
            v-if="is_minimal_display && fields.length > 5"
            v-bind:class="show_more_class"
        >
            <translate>Show more</translate>
        </button>
    </div>
</template>

<script>
export default {
    name: "FieldErrorMessage",
    props: {
        fields: Array,
        type: String,
    },
    data() {
        return { is_minimal_display: true };
    },
    computed: {
        fields_to_display() {
            if (this.is_minimal_display) {
                return this.fields.slice(0, 5);
            }
            return this.fields;
        },
        show_more_class() {
            return (
                "btn btn-link move-artifact-display-more-field move-artifact-display-more-field-" +
                this.type
            );
        },
    },
};
</script>
