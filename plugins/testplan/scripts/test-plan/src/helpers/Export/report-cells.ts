/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

export type ReportCell = TextCell | DateCell | HTMLCell | NumberCell | EmptyCell;

abstract class CommentCell {
    readonly comment?: string;

    withComment(comment: string): this {
        return Object.assign(Object.create(Object.getPrototypeOf(this)), this, { comment });
    }
}

export class TextCell extends CommentCell {
    readonly type = "text";

    constructor(readonly value: string) {
        super();
    }
}

export class HTMLCell extends CommentCell {
    readonly type = "html";

    constructor(readonly value: string) {
        super();
    }
}

export class DateCell extends CommentCell {
    readonly type = "date";

    constructor(readonly value: Date) {
        super();
    }
}

export class NumberCell extends CommentCell {
    readonly type = "number";

    constructor(readonly value: number) {
        super();
    }
}

export class EmptyCell extends CommentCell {
    readonly type = "empty";
}
