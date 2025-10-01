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
    <ul>
        <slot />
    </ul>
</template>

<style scoped lang="scss">
@use "@/themes/includes/viewport-breakpoint";

ul {
    --timeline-color: var(--tlp-main-color-lighter-80);
    --timeline-color-for-version-with-title: var(--tlp-main-color-lighter-50);

    position: relative;
    padding: 0 0 var(--tlp-medium-spacing);
    list-style-type: none;
    color: var(--tlp-dimmed-color);

    &::after {
        content: "specimen";
        position: absolute;
        top: 20vh;
        transform: rotate(310deg);
        opacity: 0.7;
        color: var(--tlp-main-color-transparent-90);
        font-size: 5rem;
        font-weight: 600;
        pointer-events: none;
    }

    &:has(+ .load-more-versions) {
        padding: 0;
    }
}
</style>
