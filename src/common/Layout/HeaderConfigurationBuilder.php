<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use Tuleap\Layout\HeaderConfiguration\InProject;
use Tuleap\Layout\HeaderConfiguration\InProjectWithoutSidebar\BackToLinkPresenter;
use Tuleap\Layout\HeaderConfiguration\WithoutSidebar;

final class HeaderConfigurationBuilder
{
    /**
     * @var string[]
     */
    private array $body_class = [];
    /**
     * @var string[]
     */
    private array $main_class = [];

    private ?InProject $in_project = null;

    private function __construct(private string $title)
    {
    }

    public static function get(string $title): self
    {
        return new self($title);
    }

    /**
     * @param string[] $body_class
     */
    public function withBodyClass(array $body_class): self
    {
        $this->body_class = $body_class;

        return $this;
    }

    /**
     * @param string[] $main_class
     */
    public function withMainClass(array $main_class): self
    {
        $this->main_class = $main_class;

        return $this;
    }

    public function inProjectWithoutSidebar(BackToLinkPresenter $back_to_link): self
    {
        $this->in_project = new InProject(
            new WithoutSidebar($back_to_link)
        );

        return $this;
    }

    public function build(): HeaderConfiguration
    {
        return new HeaderConfiguration(
            $this->title,
            $this->in_project,
            $this->body_class,
            $this->main_class,
        );
    }
}
