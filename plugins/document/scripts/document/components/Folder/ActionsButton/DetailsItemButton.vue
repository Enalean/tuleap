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
    <button
        v-on:click="goToDetails"
        class="tlp-button-primary"
        v-bind:class="buttonClass"
        data-test="docman-go-to-details"
    >
        <i class="fa fa-list tlp-button-icon"></i>
        <translate>Details</translate>
    </button>
</template>

<script setup lang="ts">
import { redirectToUrl } from "../../../helpers/location-helper";
import type { Item } from "../../../type";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";

const props = defineProps<{ item: Item; buttonClass: string }>();

const { project_id } = useNamespacedState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

function goToDetails(): void {
    redirectToUrl(
        `/plugins/docman/?group_id=${encodeURIComponent(project_id.value)}&id=${encodeURIComponent(
            props.item.id
        )}&action=details&section=details`
    );
}
</script>
<script lang="ts">
import { defineComponent } from "@vue/composition-api";
export default defineComponent({});
</script>
