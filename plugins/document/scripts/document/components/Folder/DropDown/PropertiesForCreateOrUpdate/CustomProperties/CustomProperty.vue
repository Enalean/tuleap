<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
        <div
            v-for="property in itemProperty"
            v-bind:key="property.short_name"
            class="document-properties-margin"
        >
            <custom-property-component-type-renderer v-bind:item-property="property" />
        </div>
    </div>
</template>

<script setup lang="ts">
import CustomPropertyComponentTypeRenderer from "./CustomPropertyComponentTypeRenderer.vue";
import type { Property } from "../../../../../type";

defineProps<{ itemProperty: Array<Property> }>();
</script>
