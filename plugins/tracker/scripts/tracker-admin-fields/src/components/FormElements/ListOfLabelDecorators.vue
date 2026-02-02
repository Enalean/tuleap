<!--
  - Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    <label-decorator
        v-for="(decorator, key) of decorators"
        v-bind:key="key"
        v-bind:decorator="decorator"
    />
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { BaseFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { TRACKER_SEMANTICS } from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import LabelDecorator from "./LabelDecorator.vue";

const props = defineProps<{
    field: BaseFieldStructure;
}>();

const { $gettext } = useGettext();

const semantics = strictInject(TRACKER_SEMANTICS);
const decorators = computed(() => [
    ...semantics.getForField(props.field, $gettext),
    ...(props.field.has_notifications
        ? [
              {
                  icon: "fa-solid fa-bell",
                  label: $gettext("Notifications"),
                  description: $gettext("This field is used to send notifications"),
              },
          ]
        : []),
]);
</script>
