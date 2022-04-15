<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <span class="baseline-cross-ref-badge cross-ref-badge" v-bind:class="artifact_badge_class">
        {{ tracker.item_name }} #{{ artifact.id }}
    </span>
</template>
<script>
export default {
    name: "ArtifactBadge",
    props: {
        artifact: {
            required: true,
            type: Object,
        },
        tracker: {
            required: true,
            type: Object,
        },
    },
    computed: {
        artifact_badge_class() {
            const normalized_color = this.tracker.color_name.replace("_", "-");
            return `tlp-swatch-${normalized_color}`;
        },
    },
};
</script>
