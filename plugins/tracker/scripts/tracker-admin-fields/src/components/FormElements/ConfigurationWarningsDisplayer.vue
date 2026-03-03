<!--
  - Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
    <div v-if="warnings.length > 0" class="tlp-alert-warning" data-test="configuration-warnings">
        <p class="tlp-alert-title">{{ $gettext("Configuration warnings") }}</p>
        <ul>
            <li
                v-for="(warning, warning_index) in warnings"
                v-bind:key="warning_index"
                data-test="warning"
            >
                {{ warning.message }}
                <template v-if="'links' in warning">
                    <template v-for="(link, link_index) in warning.links" v-bind:key="link_index">
                        <a v-bind:href="link.url" data-test="link">{{ link.label }}</a>
                        <template v-if="shouldDisplaySeparationComma(warning.links, link_index)"
                            >,
                        </template>
                    </template>
                </template>
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { FIELDS_CONFIGURATION_WARNINGS } from "../../injection-symbols";
import type { ConfigurationWarningLink } from "../../type";

const { $gettext } = useGettext();
const props = defineProps<{ field_id: number }>();

const warnings = strictInject(FIELDS_CONFIGURATION_WARNINGS)[props.field_id] ?? [];

const shouldDisplaySeparationComma = (
    links: readonly ConfigurationWarningLink[],
    link_index: number,
): boolean => {
    if (links.length === 1) {
        return false;
    }

    return link_index < links.length - 1;
};
</script>

<style scoped lang="scss">
ul {
    padding: 0;
    list-style-type: none;
}
</style>
