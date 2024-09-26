<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
    <div class="creation-info-panel">
        <span class="creation-info">
            {{ $gettext("Created:") }}
            <tlp-relative-date
                data-test="pull-request-creation-date"
                v-bind:date="pull_request.creation_date"
                v-bind:absolute-date="formatted_full_date"
                v-bind:placement="relative_date_placement"
                v-bind:preference="relative_date_preference"
                v-bind:locale="user_locale"
            >
                {{ formatted_full_date }}
            </tlp-relative-date>
        </span>

        <span class="creation-info">
            {{ $gettext("Created by:") }}
            <span class="tlp-avatar-mini">
                <img
                    v-bind:src="pull_request.creator.avatar_url"
                    aria-hidden="true"
                    data-test="pull-request-creator-avatar-img"
                />
            </span>
            <a
                class="creator-name"
                data-test="pull-request-creator-link"
                v-bind:href="pull_request.creator.user_url"
                >{{ pull_request.creator.display_name }}</a
            >
        </span>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { relativeDatePlacement, relativeDatePreference } from "@tuleap/tlp-relative-date";
import { IntlFormatter } from "@tuleap/date-helper";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    USER_TIMEZONE_KEY,
} from "../../../injection-symbols";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request: PullRequest;
}>();

const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);
const user_locale = strictInject(USER_LOCALE_KEY);
const user_timezone = strictInject(USER_TIMEZONE_KEY);

const formatter = IntlFormatter(user_locale, user_timezone, "date-with-time");
const formatted_full_date = computed((): string =>
    formatter.format(props.pull_request.creation_date),
);

const relative_date_preference = computed((): string => {
    return relativeDatePreference(relative_date_display);
});

const relative_date_placement = computed((): string => {
    return relativeDatePlacement(relative_date_display, "right");
});
</script>

<style scoped lang="scss">
.creation-info-panel {
    display: flex;
    align-items: center;
    color: var(--tlp-dimmed-color);
    font-size: 0.75rem;
    gap: var(--tlp-medium-spacing);
}

.creation-info {
    display: flex;
    align-items: center;
    gap: 4px;
}

.creator-name {
    color: var(--tlp-dimmed-color);
}
</style>
