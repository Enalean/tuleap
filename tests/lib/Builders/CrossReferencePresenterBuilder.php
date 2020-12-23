<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Test\Builders;

use Tuleap\Reference\CrossReferencePresenter;

class CrossReferencePresenterBuilder
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $type = 'type';
    /**
     * @var string
     */
    private $value = 'whatever';
    /**
     * @var int
     */
    private $project_id = 1;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function get(int $id): self
    {
        return new self($id);
    }

    public function withType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function withValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function withProjectId(int $project_id): self
    {
        $this->project_id = $project_id;

        return $this;
    }

    public function build(): CrossReferencePresenter
    {
        return new CrossReferencePresenter(
            $this->id,
            $this->type,
            'title',
            'url',
            'delete_url',
            $this->project_id,
            $this->value,
            null,
            [],
            null,
        );
    }
}
