<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <section>
        <h1 v-bind:class="{ 'version-with-title': version.title.isValue() }">
            <span>{{ title }}</span>
            <version-toggle v-if="toggle_state" v-bind:toggle_state="toggle_state" />
        </h1>
        <div class="metadata">
            <tlp-relative-date
                v-bind:date="version.created_on.toISOString()"
                v-bind:absolute-date="formatted_date"
                v-bind:placement="relative_date_placement"
                v-bind:preference="relative_date_preference"
                v-bind:locale="user_preferences.locale"
            >
                {{ formatted_date }}
            </tlp-relative-date>
            {{ $gettext("by:") }}
            <span>
                <span class="tlp-avatar-mini"
                    ><img
                        loading="lazy"
                        v-bind:src="version.created_by.avatar_url"
                        v-bind:alt="$gettext('User avatar')"
                /></span>
                {{ version.created_by.display_name }}
            </span>
        </div>
        <version-description v-bind:version="version" />
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import type { Version } from "./fake-list-of-versions";
import type { UserPreferences } from "@/user-preferences-injection-key";
import { USER_PREFERENCES } from "@/user-preferences-injection-key";
import { IntlFormatter } from "@tuleap/date-helper";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { strictInject } from "@tuleap/vue-strict-inject";
import VersionDescription from "@/components/sidebar/versions/VersionDescription.vue";
import VersionToggle from "@/components/sidebar/versions/VersionToggle.vue";
import type { ToggleState } from "@/components/sidebar/versions/toggle-state";

const props = defineProps<{ version: Version; toggle_state?: ToggleState }>();

const { $gettext } = useGettext();

const user_preferences = strictInject<UserPreferences>(USER_PREFERENCES);

const formatter = IntlFormatter(
    user_preferences.locale,
    user_preferences.timezone,
    "date-with-time",
);

const relative_date_preference = relativeDatePreference(user_preferences.relative_date_display);
const relative_date_placement = relativeDatePlacement(
    user_preferences.relative_date_display,
    "right",
);

const formatted_date = computed((): string =>
    formatter.format(props.version.created_on.toISOString()),
);

const title = computed(() => props.version.title.unwrapOr(formatted_date.value));
</script>

<style scoped lang="scss">
section {
    width: calc(100% - var(--tlp-medium-spacing));
}

h1 {
    margin: 0;
    color: inherit;
    font-size: inherit;
    font-weight: normal;
}

.version-with-title {
    font-weight: 500;
}

.metadata {
    font-size: 0.75rem;

    &:not(:last-child) {
        margin: 0 0 var(--tlp-small-spacing);
    }
}
</style>
