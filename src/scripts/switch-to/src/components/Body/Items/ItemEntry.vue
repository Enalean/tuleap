<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div
        class="switch-to-item-entry"
        v-on:click="onClick"
        v-bind:data-target-id="target_id"
        data-test="switch-to-item-entry"
    >
        <div
            class="switch-to-item-entry-with-links"
            v-bind:class="{ 'switch-to-item-entry-with-links-with-badge': entry.xref }"
        >
            <div class="switch-to-item-entry-links">
                <a
                    v-bind:href="entry.html_url"
                    v-bind:class="entry.color_name"
                    class="switch-to-item-entry-link"
                    ref="entry_link"
                    data-test="entry-link"
                    v-on:keydown="changeFocus"
                >
                    <i
                        class="fa fa-fw switch-to-item-entry-icon"
                        v-bind:class="entry.icon_name"
                        aria-hidden="true"
                    ></i>
                    <span
                        class="switch-to-item-entry-badge cross-ref-badge cross-ref-badge-on-dark-background"
                        v-bind:class="xref_color"
                        v-if="entry.xref"
                        data-test="item-xref"
                    >
                        <highlight-matching-text
                            v-bind:text="entry.xref"
                            v-on:matches="onXRefMatches"
                        />
                    </span>
                    <highlight-matching-text
                        class="switch-to-item-entry-label"
                        v-bind:text="entry.title || ''"
                        v-on:matches="onTitleMatches"
                        data-test="item-title"
                    />
                </a>
                <div class="switch-to-item-entry-quick-links" v-if="has_quick_links">
                    <quick-link
                        v-for="link of entry.quick_links"
                        v-bind:key="link.html_url"
                        class="switch-to-item-entry-quick-links-link"
                        v-bind:link="link"
                        v-bind:item="entry"
                        v-bind:project="null"
                    />
                </div>
            </div>
            <item-badge
                v-bind:badge="entry.badges[0]"
                v-if="entry.badges.length > 0"
                class="switch-to-item-entry-additional-badge"
                data-test="additional-badge"
            />
        </div>
        <div class="switch-to-item-metadata">
            <span class="switch-to-item-project">
                {{ entry.project.label }}
            </span>
            <span v-if="should_display_content_matches" data-test="item-content-matches">
                |
                {{ $gettext("Content matches:") }}
                <mark class="tlp-mark-on-dark-background">{{ keywords }}</mark>
            </span>
            <span v-if="should_display_cropped_content">
                |
                <highlight-matching-text v-bind:text="cropped_content" />
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import type { ItemDefinition } from "../../../type";
import HighlightMatchingText from "../HighlightMatchingText.vue";
import type { FocusFromItemPayload } from "../../../stores/type";
import { useKeyboardNavigationStore } from "../../../stores/keyboard-navigation";
import { storeToRefs } from "pinia";
import QuickLink from "../QuickLink.vue";
import { useRootStore } from "../../../stores/root";
import ItemBadge from "./ItemBadge.vue";

const props = defineProps<{
    entry: ItemDefinition;
    changeFocusCallback: (payload: FocusFromItemPayload) => void;
    location: Location;
}>();

const xref_color = ref<string>("tlp-swatch-" + props.entry.color_name);
const has_quick_links = ref<boolean>(props.entry.quick_links.length > 0);

const { keywords, is_in_search_mode } = storeToRefs(useRootStore());

const cropped_content = computed((): string => {
    const cropped_content = props.entry.cropped_content;
    if (cropped_content === undefined || cropped_content === null) {
        return "";
    }
    return cropped_content.trim();
});

const has_cropped_content = computed((): boolean => cropped_content.value !== "");

const matching_words_in_xref = ref<number>(0);
const matching_words_in_title = ref<number>(0);
const should_display_content_matches = computed((): boolean => {
    if (!is_in_search_mode.value) {
        return false;
    }

    if (has_cropped_content.value) {
        return false;
    }

    return matching_words_in_xref.value + matching_words_in_title.value === 0;
});

function onXRefMatches(words: string[]): void {
    matching_words_in_xref.value = words.length;
}

function onTitleMatches(words: string[]): void {
    matching_words_in_title.value = words.length;
}

const should_display_cropped_content = computed((): boolean => {
    if (!is_in_search_mode.value) {
        return false;
    }

    if (!has_cropped_content.value) {
        return false;
    }

    return matching_words_in_xref.value + matching_words_in_title.value === 0;
});

const entry_link = ref<HTMLAnchorElement | null>(null);

const navigation_store = useKeyboardNavigationStore();
const { programmatically_focused_element } = storeToRefs(navigation_store);

watch(programmatically_focused_element, () => {
    if (programmatically_focused_element.value !== props.entry) {
        return;
    }

    if (entry_link.value instanceof HTMLAnchorElement) {
        entry_link.value.focus();
    }
});

function changeFocus(event: KeyboardEvent): void {
    switch (event.key) {
        case "ArrowUp":
        case "ArrowRight":
        case "ArrowDown":
        case "ArrowLeft":
            event.preventDefault();
            props.changeFocusCallback({ entry: props.entry, key: event.key });
            break;
        default:
    }
}

const target_id = computed((): string => "switch-to-item-entry-" + encodeURI(props.entry.html_url));
function onClick(event: MouseEvent): void {
    if (!(event.target instanceof HTMLElement)) {
        return;
    }

    const closest = event.target.closest(`a, [data-target-id="${target_id.value}"]`);
    if (closest instanceof HTMLElement && closest.dataset.targetId === target_id.value) {
        props.location.assign(props.entry.html_url);
    }
}
</script>
