/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { AlignmentType, convertInchesToTwip, LevelFormat } from "docx";
import type { ILevelsOptions } from "docx";

// This is based on the default implementation of the bullets
// See https://github.com/dolanmiu/docx/blob/7.1.1/src/file/numbering/numbering.ts#L58-L158
const LEVEL_STYLES = [
    {
        paragraph: {
            indent: { left: convertInchesToTwip(0.5), hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: convertInchesToTwip(1), hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 2160, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 2880, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 3600, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 4320, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 5040, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 5760, hanging: convertInchesToTwip(0.25) },
        },
    },
    {
        paragraph: {
            indent: { left: 6480, hanging: convertInchesToTwip(0.25) },
        },
    },
];

function applyStyleOnLevel(levels_options: ReadonlyArray<ILevelsOptions>): ILevelsOptions[] {
    return levels_options.map((level_options): ILevelsOptions => {
        const style = LEVEL_STYLES[level_options.level] ?? LEVEL_STYLES.slice(-1)[0];
        return { ...level_options, style };
    });
}

export const HTML_ORDERED_LIST_NUMBERING = {
    levels: applyStyleOnLevel([
        {
            level: 0,
            format: LevelFormat.DECIMAL,
            text: "%1.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 1,
            format: LevelFormat.DECIMAL,
            text: "%2.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 2,
            format: LevelFormat.DECIMAL,
            text: "%3.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 3,
            format: LevelFormat.DECIMAL,
            text: "%4.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 4,
            format: LevelFormat.DECIMAL,
            text: "%5.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 5,
            format: LevelFormat.DECIMAL,
            text: "%6.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 6,
            format: LevelFormat.DECIMAL,
            text: "%7.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 7,
            format: LevelFormat.DECIMAL,
            text: "%8.",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 8,
            format: LevelFormat.DECIMAL,
            text: "%9.",
            alignment: AlignmentType.LEFT,
        },
    ]),

    reference: "html-ordered-list",
};

export const HTML_UNORDERED_LIST_NUMBERING = {
    levels: applyStyleOnLevel([
        {
            level: 0,
            format: LevelFormat.BULLET,
            text: "\u25CF",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 1,
            format: LevelFormat.BULLET,
            text: "\u25CB",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 2,
            format: LevelFormat.BULLET,
            text: "\u25A0",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 3,
            format: LevelFormat.BULLET,
            text: "\u25CF",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 4,
            format: LevelFormat.BULLET,
            text: "\u25CB",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 5,
            format: LevelFormat.BULLET,
            text: "\u25A0",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 6,
            format: LevelFormat.BULLET,
            text: "\u25CF",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 7,
            format: LevelFormat.BULLET,
            text: "\u25CF",
            alignment: AlignmentType.LEFT,
        },
        {
            level: 8,
            format: LevelFormat.BULLET,
            text: "\u25CF",
            alignment: AlignmentType.LEFT,
        },
    ]),

    reference: "html-unordered-list",
};
