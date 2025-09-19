/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { Project } from "../../src/configuration-keys";
import { ProjectPrivacyBuilder } from "./ProjectPrivacyBuilder";

export class ProjectBuilder {
    private readonly id: number;
    private name: string = "my-project";
    private public_name: string = "My Project";
    private url: string = "/project/my-project";
    private icon: string = "";

    constructor(id: number) {
        this.id = id;
    }

    public withName(name: string): this {
        this.name = name;
        return this;
    }

    public withPublicName(public_name: string): this {
        this.public_name = public_name;
        return this;
    }

    public withUrl(url: string): this {
        this.url = url;
        return this;
    }

    public withIcon(icon: string): this {
        this.icon = icon;
        return this;
    }

    public build(): Project {
        return {
            flags: [],
            icon: this.icon,
            id: this.id,
            name: this.name,
            privacy: ProjectPrivacyBuilder.private(),
            public_name: this.public_name,
            url: this.url,
        };
    }
}
