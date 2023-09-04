<!--
  - Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div>
        <div class="tlp-form-element">
            <label class="tlp-label tlp-checkbox">
                <input
                    type="checkbox"
                    data-test="delete-associated-wiki-page-checkbox"
                    v-on:click="processInput"
                />
                <span>{{ $gettext("Propagate deletion to wiki service") }}</span>
            </label>
            <p class="tlp-text-warning">
                {{
                    $gettext(
                        "Please note that if you check this option, the referenced wiki page will no longer exist in the wiki service too.",
                    )
                }}
            </p>
        </div>
        <div
            class="tlp-alert-warning"
            v-if="is_option_checked && wikiPageReferencers.length > 0"
            data-test="delete-associated-wiki-page-warning-message"
        >
            <p>{{ wiki_deletion_warning }}</p>
            <ul>
                <li v-for="referencer in wikiPageReferencers" v-bind:key="referencer.id">
                    <a
                        v-bind:href="getWikiPageUrl(referencer)"
                        class="wiki-page-referencer-link"
                        data-test="wiki-page-referencer-link"
                    >
                        {{ referencer.path }}
                    </a>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { sprintf } from "sprintf-js";
import type { Wiki } from "../../../../type";
import type { ItemPath } from "../../../../store/actions-helpers/build-parent-paths";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../../store/configuration";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{ item: Wiki; wikiPageReferencers: Array<ItemPath> }>();

const { project_id } = useNamespacedState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

const is_option_checked = ref(false);

const { $gettext } = useGettext();
const wiki_deletion_warning = computed((): string => {
    return sprintf(
        $gettext(
            'You should also be aware that the following wiki documents referencing page "%s" will no longer be valid if you choose to propagate the deletion to the wiki service:',
        ),
        props.item.wiki_properties.page_name,
    );
});

const emit = defineEmits<{
    (e: "input", { delete_associated_wiki_page: boolean }): void;
}>();

function processInput($event: Event): void {
    const event_target = $event.target;

    if (event_target instanceof HTMLInputElement) {
        const is_checked = event_target.checked;

        emit("input", { delete_associated_wiki_page: is_checked });

        is_option_checked.value = is_checked;
    }
}

function getWikiPageUrl(referencer: ItemPath): string {
    return `/plugins/docman/?group_id=${encodeURIComponent(
        project_id.value,
    )}&action=show&id=${encodeURIComponent(referencer.id)}`;
}
</script>
