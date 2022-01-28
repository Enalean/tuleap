/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

/**
 * Used to coerce a nominal type system into TypeScript's structural type system.
 * Without this, TypeScript will consider different types that have the same "shape" to be compatible.
 */
export type Identifier<TypeOfIdentifier extends string> = {
    _type: TypeOfIdentifier;
    id: number;
};

/*
 * PHP uses a "nominal type system" wherein instance of different classes with the same "id" field are not compatibles.
 * For example, an instance of ArtifactIdentifier is not compatible with TrackerIdentifier even though they have the
 * same "shape" with a public "id" field. Their "names" are not compatible.
 *
 * TypeScript uses a "structural type system" wherein two objects with the same "shape" are compatible. As long as one
 * object has at least all the properties defined by an interface, it is considered valid for that interface.
 *
 * Structural type systems are more flexible and easier to use, but in some cases we want to be strict. We don't want
 * an ArtifactIdentifier to be allowed when we expect a TrackerIdentifier, even though they have the same shape.
 * There are several ways to "force incompatibility", this one has the advantage of avoiding type assertions.
 */
