<?php
/**
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Test\Builders;

use TemplateRendererFactory;
use Tuleap\Templating\TemplateCacheInterface;

class TemplateRendererFactoryBuilder
{
    private $path;

    public static function get(): self
    {
        return new self();
    }

    /**
     * @return $this
     */
    public function withPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function build(): TemplateRendererFactory
    {
        if ($this->path === null) {
            throw new \RuntimeException('You must pass a valid path with `withPath`. Eg. withPath($this->getTmpDir())');
        }
        $renderer = new class ($this->path) implements TemplateCacheInterface {
            /**
             * @var string
             */
            private $path;

            public function __construct(string $path)
            {
                $this->path = $path;
            }

            public function getPath(): ?string
            {
                return $this->path;
            }

            public function invalidate(): void
            {
            }
        };
        return new TemplateRendererFactory($renderer);
    }
}
