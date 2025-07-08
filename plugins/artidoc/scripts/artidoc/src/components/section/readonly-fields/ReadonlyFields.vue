<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div class="section-fields tlp-card tlp-card-inactive document-grid-4-columns">
        <div
            v-for="(readonly_field, index) in section.fields"
            v-bind:key="index"
            class="tlp-property"
            v-bind:class="getFieldClasses(readonly_field)"
            data-test="readonly-field"
        >
            <field-string
                v-if="readonly_field.type === STRING_FIELD"
                v-bind:field_string="readonly_field"
            />
            <field-user-groups-list
                v-if="readonly_field.type === USER_GROUP_LIST_FIELD"
                v-bind:user_groups_list_field="readonly_field"
            />
            <field-static-list
                v-if="readonly_field.type === STATIC_LIST_FIELD"
                v-bind:static_list_field="readonly_field"
            />
            <field-user-list
                v-if="readonly_field.type === USER_LIST_FIELD"
                v-bind:user_list_field="readonly_field"
            />
            <field-links v-if="readonly_field.type === LINKS_FIELD" v-bind:field="readonly_field" />
        </div>
    </div>
</template>

<script setup lang="ts">
import FieldString from "@/components/section/readonly-fields/FieldString.vue";
import type { ReadonlyField } from "@/sections/readonly-fields/ReadonlyFields";
import {
    LINKS_FIELD,
    STATIC_LIST_FIELD,
    STRING_FIELD,
    USER_GROUP_LIST_FIELD,
    USER_LIST_FIELD,
} from "@/sections/readonly-fields/ReadonlyFields";
import type { SectionBasedOnArtifact } from "@/helpers/artidoc-section.type";
import FieldUserGroupsList from "@/components/section/readonly-fields/FieldUserGroupsList.vue";
import FieldStaticList from "@/components/section/readonly-fields/FieldStaticList.vue";
import FieldUserList from "@/components/section/readonly-fields/FieldUserList.vue";
import FieldLinks from "@/components/section/readonly-fields/FieldLinks.vue";
import { DISPLAY_TYPE_BLOCK } from "@/sections/readonly-fields/AvailableReadonlyFields";

defineProps<{
    section: SectionBasedOnArtifact;
}>();

const getFieldClasses = (readonly_field: ReadonlyField): string[] => {
    if (readonly_field.display_type === DISPLAY_TYPE_BLOCK) {
        return ["display-field-in-block", "document-grid-element-full-row"];
    }

    return [];
};
</script>

<style scoped lang="scss">
.section-fields {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr;
    margin: var(--tlp-medium-spacing) 0 0;
    overflow-wrap: normal;
    gap: var(--tlp-small-spacing);

    > .display-field-in-block {
        grid-column-start: span 4;
    }
}
</style>
