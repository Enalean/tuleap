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
    <div v-if="is_old_version_displayed">
        <i class="fa-solid fa-lock" aria-hidden="true"></i>
        {{ message }}
    </div>
</template>

<script setup lang="ts">
import { strictInject } from "@tuleap/vue-strict-inject";
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { CURRENT_VERSION_DISPLAYED } from "./current-version-displayed";
import type { Version } from "./sidebar/versions/fake-list-of-versions";
import type { UserPreferences } from "@/user-preferences-injection-key";
import { USER_PREFERENCES } from "@/user-preferences-injection-key";
import { IntlFormatter } from "@tuleap/date-helper";
import { USE_FAKE_VERSIONS } from "@/use-fake-versions-injection-key";

const { $gettext } = useGettext();

const current_version_displayed = strictInject(CURRENT_VERSION_DISPLAYED);
const use_fake_versions = strictInject(USE_FAKE_VERSIONS);

const is_old_version_displayed = computed(() =>
    current_version_displayed.old_version.value.isValue(),
);

const user_preferences = strictInject<UserPreferences>(USER_PREFERENCES);

const formatter = IntlFormatter(
    user_preferences.locale,
    user_preferences.timezone,
    "date-with-time",
);

const title = computed(() =>
    current_version_displayed.old_version.value.match(
        (version: Version) =>
            version.title.unwrapOr(formatter.format(version.created_on.toISOString())),
        () => "",
    ),
);

const message = computed(() => {
    if (use_fake_versions.value) {
        return $gettext(
            'You will be able to display "%{ title }" a read-only version (not yet implemented).',
            { title: title.value },
        );
    }

    return $gettext('You are currently viewing "%{ title }" in read-only mode.', {
        title: title.value,
    });
});
</script>

<style scoped lang="scss">
@use "@/themes/includes/zindex";

div {
    display: flex;
    position: sticky;
    z-index: zindex.$toolbar;
    top: var(--artidoc-sticky-top-position);
    align-items: center;
    width: 100%;
    min-height: var(--tlp-jumbo-spacing);
    padding: var(--tlp-small-spacing) var(--tlp-medium-spacing);
    border-bottom: 1px solid var(--tlp-neutral-normal-color);
    background: var(--tlp-white-color);
    color: var(--tlp-info-color);
    gap: var(--tlp-small-spacing);
}

.artidoc-container-scrolled div {
    border-bottom: 0;
    box-shadow: var(--tlp-sticky-header-shadow);
}
</style>
