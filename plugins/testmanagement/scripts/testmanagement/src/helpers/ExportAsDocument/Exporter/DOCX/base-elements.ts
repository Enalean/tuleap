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

import { XmlAttributeComponent, XmlComponent } from "docx";

type FieldCharacterType = "begin" | "end" | "separate";

class FidCharAttrs extends XmlAttributeComponent<{
    readonly type: FieldCharacterType;
    readonly dirty?: boolean | undefined;
}> {
    protected override readonly xmlKeys = { type: "w:fldCharType", dirty: "w:dirty" };
}

export class ComplexFieldCharacter extends XmlComponent {
    constructor(type: FieldCharacterType, dirty?: boolean) {
        super("w:fldChar");
        this.root.push(new FidCharAttrs({ type: type, dirty }));
    }
}

type SpaceType = "preserve" | "default";

export class TextAttributes extends XmlAttributeComponent<{ readonly space: SpaceType }> {
    protected override readonly xmlKeys = { space: "xml:space" };
}
