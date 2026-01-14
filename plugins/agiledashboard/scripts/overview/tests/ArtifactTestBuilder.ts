/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import type { Artifact } from "../src/types";

export class ArtifactTestBuilder {
    private readonly id: number;
    private label: string = "label";
    private status: string = "status";

    constructor(id: number) {
        this.id = id;
    }

    public withLabel(label: string): this {
        this.label = label;
        return this;
    }

    public withStatus(status: string): this {
        this.status = status;
        return this;
    }

    public build(): Artifact {
        return {
            id: this.id,
            label: this.label,
            short_type: "",
            status: "",
            full_status: {
                value: this.status,
                color: null,
            },
            color: "",
            parent: null,
        };
    }
}
