<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">{{ $gettext("References") }}</h1>
            </div>
            <section class="tlp-pane-section">
                <references-list-loading v-if="is_loading" />

                <template v-else>
                    <references-list-error
                        v-if="error_message !== ''"
                        v-bind:error_message="error_message"
                    />

                    <template v-else-if="references !== null">
                        <div
                            v-if="!references.has_source && !references.has_target"
                            data-test="empty-state"
                        >
                            <span>{{ $gettext("References list is empty") }}</span>
                        </div>

                        <template v-if="references.has_source">
                            <h5>{{ $gettext("Referencing document") }}</h5>
                            <cross-reference
                                v-for="reference in references.sources_by_nature"
                                v-bind:key="reference.label"
                                v-bind:reference="reference"
                            />
                        </template>

                        <template v-if="references.has_target">
                            <h5>{{ $gettext("Referenced by document") }}</h5>
                            <cross-reference
                                v-for="reference in references.targets_by_nature"
                                v-bind:key="reference.label"
                                v-bind:reference="reference"
                            />
                        </template>
                    </template>
                </template>
            </section>
        </div>
    </section>
</template>

<script setup lang="ts">
import type { Item } from "../../type";
import { useGettext } from "vue3-gettext";
import { onBeforeMount, ref } from "vue";
import type { CrossReferenceByDirection } from "../../api/references-rest-querier";
import { getItemReferences } from "../../api/references-rest-querier";
import ReferencesListLoading from "./ReferencesListLoading.vue";
import ReferencesListError from "./ReferencesListError.vue";
import CrossReference from "./CrossReference.vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT } from "../../configuration-keys";

const { $gettext } = useGettext();

const props = defineProps<{ item: Item }>();

const is_loading = ref<boolean>(true);
const error_message = ref<string>("");
const references = ref<CrossReferenceByDirection | null>(null);

const project = strictInject(PROJECT);

onBeforeMount(() => {
    getItemReferences(props.item.id, project.id).match(
        (result) => {
            references.value = result;
            is_loading.value = false;
        },
        (fault) => {
            error_message.value = fault.toString();
            is_loading.value = false;
        },
    );
});
</script>
