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
    <div class="tlp-property">
        <label v-bind:for="property_label" class="tlp-label" data-test="properties-list-label">
            {{ property_name }}
        </label>
        <p v-bind:id="property_label">
            <quick-look-property-date
                v-if="property.type === PROPERTY_DATE_TYPE"
                v-bind:property="property"
            />
            <template v-else-if="property.type === PROPERTY_LIST_TYPE && !is_list_empty">
                <ul v-if="property.list_value.length > 1">
                    <li v-for="value in property.list_value" v-bind:key="value.id">
                        {{ value.name }}
                    </li>
                </ul>
                <template v-else>
                    {{ property.list_value[0].name }}
                </template>
            </template>

            <span
                class="document-quick-look-property-empty"
                v-else-if="!has_property_a_value"
                v-translate
            >
                Empty
            </span>
            <template v-else>
                <div v-dompurify-html="property.post_processed_value"></div>
            </template>
        </p>
    </div>
</template>
<script>
import { PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME } from "../../../constants";
import QuickLookPropertyDate from "./QuickLookPropertyDate.vue";

export default {
    name: "QuickLookDocumentAdditionalProperties",
    components: { QuickLookPropertyDate },
    props: {
        property: Object,
    },
    data() {
        return {
            PROPERTY_LIST_TYPE: "list",
            PROPERTY_DATE_TYPE: "date",
        };
    },
    computed: {
        property_label() {
            return `document-${this.property.short_name}`;
        },
        property_name() {
            if (this.isPropertyObsolesenceDate()) {
                return this.$gettext("Validity");
            }
            return this.property.name;
        },
        is_list_empty() {
            return !this.property.list_value || !this.property.list_value.length;
        },
        has_property_a_value() {
            if (this.property.type === this.PROPERTY_LIST_TYPE) {
                return !this.is_list_empty;
            }

            return this.property.value;
        },
    },
    methods: {
        isPropertyObsolesenceDate() {
            return this.property.short_name === PROPERTY_OBSOLESCENCE_DATE_SHORT_NAME;
        },
    },
};
</script>
