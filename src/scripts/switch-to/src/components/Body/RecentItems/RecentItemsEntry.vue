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
    <div class="switch-to-recent-items-entry" v-on:keydown="changeFocus">
        <div
            class="switch-to-recent-items-entry-with-links"
            v-bind:class="{ 'switch-to-recent-items-entry-with-links-with-badge': entry.xref }"
        >
            <a
                v-bind:href="entry.html_url"
                v-bind:class="entry.color_name"
                class="switch-to-recent-items-entry-link"
                ref="entry_link"
                data-test="entry-link"
            >
                <i
                    class="fa fa-fw switch-to-recent-items-entry-icon"
                    v-bind:class="entry.icon_name"
                    aria-hidden="true"
                ></i>
                <word-highlighter
                    v-bind:query="filter_value"
                    v-bind:highlight-class="'tlp-mark-on-dark-background'"
                    class="switch-to-recent-items-entry-badge cross-ref-badge cross-ref-badge-on-dark-background"
                    v-bind:class="xref_color"
                    v-if="entry.xref"
                >
                    {{ entry.xref }}
                </word-highlighter>
                <word-highlighter
                    v-bind:query="filter_value"
                    v-bind:highlight-class="'tlp-mark-on-dark-background'"
                    class="switch-to-recent-items-entry-label"
                >
                    {{ entry.title }}
                </word-highlighter>
            </a>
            <div class="switch-to-recent-items-entry-quick-links" v-if="has_quick_links">
                <a
                    v-for="link of entry.quick_links"
                    v-bind:key="link.html_url"
                    v-bind:href="link.html_url"
                    v-bind:title="link.name"
                    class="switch-to-recent-items-entry-quick-links-link"
                >
                    <i class="fa" v-bind:class="link.icon_name"></i>
                </a>
            </div>
        </div>
        <span class="switch-to-recent-items-project">
            {{ entry.project.label }}
        </span>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Watch } from "vue-property-decorator";
import type { UserHistoryEntry } from "../../../type";
import WordHighlighter from "vue-word-highlighter";
import { useSwitchToStore } from "../../../stores";

@Component({
    components: { WordHighlighter },
})
export default class RecentItemsEntry extends Vue {
    @Prop({ required: true })
    private readonly entry!: UserHistoryEntry;

    @Prop({ required: true })
    private readonly has_programmatically_focus!: boolean;

    get filter_value(): string {
        return useSwitchToStore().filter_value;
    }

    @Watch("has_programmatically_focus")
    forceFocus(): void {
        if (!this.has_programmatically_focus) {
            return;
        }

        const link = this.$refs.entry_link;
        if (link instanceof HTMLAnchorElement) {
            link.focus();
        }
    }

    changeFocus(event: KeyboardEvent): void {
        switch (event.key) {
            case "ArrowUp":
            case "ArrowRight":
            case "ArrowDown":
            case "ArrowLeft":
                event.preventDefault();
                useSwitchToStore().changeFocusFromHistory({ entry: this.entry, key: event.key });
                break;
            default:
        }
    }

    get xref_color(): string {
        return "tlp-swatch-" + this.entry.color_name;
    }

    get has_quick_links(): boolean {
        return this.entry.quick_links.length > 0;
    }
}
</script>
