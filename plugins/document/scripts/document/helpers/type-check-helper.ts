/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import type {
    DefaultFileItem,
    Embedded,
    Empty,
    FakeItem,
    Folder,
    Item,
    ItemFile,
    Link,
    Wiki,
} from "../type";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";

export function isFile(
    item: Item | Embedded | Empty | ItemFile | Link | Wiki | FakeItem | DefaultFileItem,
): item is ItemFile {
    return item.type === TYPE_FILE;
}

export function isEmpty(item: Item | Embedded | Empty | ItemFile | Link | Wiki): item is Empty {
    return item.type === TYPE_EMPTY;
}

export function isLink(item: Item | Embedded | Empty | ItemFile | Link | Wiki): item is Link {
    return item.type === TYPE_LINK;
}

export function isWiki(item: Item | Embedded | Empty | ItemFile | Link | Wiki): item is Wiki {
    return item.type === TYPE_WIKI;
}

export function isEmbedded(
    item: Item | Embedded | Empty | ItemFile | Link | Wiki,
): item is Embedded {
    return item.type === TYPE_EMBEDDED;
}

export function isFolder(
    item: Item | Embedded | Empty | ItemFile | Link | Wiki | Folder | FakeItem,
): item is Folder {
    return item.type === TYPE_FOLDER;
}

export function isFakeItem(item: Item | FakeItem): item is FakeItem {
    return Object.prototype.hasOwnProperty.call(item, "progress");
}
