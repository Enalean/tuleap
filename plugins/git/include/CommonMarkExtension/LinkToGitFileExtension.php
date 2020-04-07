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
 */

declare(strict_types=1);

namespace Tuleap\Git\CommonMarkExtension;

use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

final class LinkToGitFileExtension implements ExtensionInterface
{
    /**
     * @var LinkToGitFileBlobFinder
     */
    private $git_file_blob_finder;

    public function __construct(LinkToGitFileBlobFinder $git_file_blob_finder)
    {
        $this->git_file_blob_finder = $git_file_blob_finder;
    }

    public function register(ConfigurableEnvironmentInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new LinkToGitFileProcessor($this->git_file_blob_finder)
        );
    }
}
