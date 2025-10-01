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
    <li>
        <slot />
    </li>
</template>

<script setup lang="ts"></script>
<style scoped lang="scss">
@use "@/themes/includes/versions";

$border-width: 5px;
$timeline-width: 4px;
$timeline-whitespace: var(--tlp-small-spacing);
$timeline-offset-left: calc(
    $timeline-whitespace + 0.5 * #{versions.$disc-width} - 0.5 * $timeline-width
);

li {
    display: flex;
    position: relative;
    align-items: baseline;
    padding: calc(var(--tlp-small-spacing) / 2) var(--tlp-medium-spacing)
        calc(var(--tlp-small-spacing) / 2) #{$timeline-whitespace};
    border-left: #{$border-width} solid transparent;
    gap: var(--tlp-small-spacing);

    &:first-child {
        border-left-color: var(--tlp-main-color);
        background: var(--tlp-main-color-hover-background);
    }

    &:hover {
        background: var(--tlp-main-color-hover-background);
    }

    &::before {
        content: "";
        position: absolute;
        top: 0;
        left: #{$timeline-offset-left};
        width: #{$timeline-width};
        height: 100%;
        background: var(--timeline-color);
    }

    &:first-child::before {
        top: var(--tlp-large-spacing);
        height: calc(100% - var(--tlp-medium-spacing));
    }

    &:last-child::before {
        height: var(--tlp-large-spacing);
    }
}

ul:has(+ .load-more-versions) > li:last-of-type::before {
    height: 100%;
    background: linear-gradient(
        to top,
        transparent 0,
        var(--timeline-color) var(--tlp-large-spacing)
    );
}
</style>
