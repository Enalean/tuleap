<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\Commit;

use Psr\Log\LoggerInterface;
use SVN_CommitToTagDeniedException;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVNCore\Repository;

final class ImmutableTagCommitValidator implements PathValidator
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ImmutableTagFactory
     */
    private $immutable_tag_factory;
    /**
     * @var ?ImmutableTag
     */
    private $immutable_tag;

    public function __construct(LoggerInterface $logger, ImmutableTagFactory $immutable_tag_factory)
    {
        $this->logger                = $logger;
        $this->immutable_tag_factory = $immutable_tag_factory;
    }

    /**
     * @throws SVN_CommitToTagDeniedException
     */
    public function assertPathIsValid(Repository $repository, string $transaction, string $path): void
    {
        $this->assertCommitIsNotDoneInImmutableTag($repository, $path);
    }

    /**
     * @throws SVN_CommitToTagDeniedException
     */
    private function assertCommitIsNotDoneInImmutableTag(Repository $repository, string $path): void
    {
        if ($this->immutable_tag === null) {
            $this->immutable_tag = $this->immutable_tag_factory->getByRepositoryId($repository);
        }
        $this->logger->debug("Checking if commit is done in tag: $path");
        foreach ($this->immutable_tag->getPaths() as $immutable_path) {
            if ($this->isCommitForbidden($this->immutable_tag, $immutable_path, $path)) {
                throw new SVN_CommitToTagDeniedException("Commit to tag `$immutable_path` is not allowed");
            }
        }
    }

    private function isCommitForbidden(ImmutableTag $immutable_tag, string $immutable_path, string $path): bool
    {
        $immutable_path_regexp = $this->getWellFormedRegexImmutablePath($immutable_path);

        $pattern = "%^(?:
            (?:U|D)\s+$immutable_path_regexp            # U  moduleA/tags/v1
                                                        # U  moduleA/tags/v1/toto
            |
            A\s+" . $immutable_path_regexp . "/[^/]+/[^/]+  # A  moduleA/tags/v1/toto
            )%x";

        if (preg_match($pattern, $path)) {
            return ! $this->isCommitDoneOnWhitelistElement($immutable_tag, $path);
        }

        return false;
    }

    private function isCommitDoneOnWhitelistElement(ImmutableTag $immutable_tag, string $path): bool
    {
        $whitelist = $immutable_tag->getWhitelist();
        if (! $whitelist) {
            return false;
        }

        $whitelist_regexp = [];
        foreach ($whitelist as $whitelist_path) {
            $whitelist_regexp[] = $this->getWellFormedRegexImmutablePath($whitelist_path);
        }

        foreach ($whitelist_regexp as $allowed_tag) {
            $pattern = "%^
                A\s+(?:$allowed_tag)/[^/]+/?$  # A  tags/moduleA/v1/     (allowed)
                                               # A  tags/moduleA/toto    (allowed)
                                               # A  tags/moduleA/v1/toto (forbidden)
                %x";

            if (preg_match($pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private function getWellFormedRegexImmutablePath(string $immutable_path): string
    {
        $immutable_path = trim($immutable_path, '/');
        $immutable_path = preg_quote($immutable_path, '%');
        $immutable_path = str_replace('\*', '[^/]+', $immutable_path);
        $immutable_path = str_replace(" ", "\s", $immutable_path);

        return $immutable_path;
    }
}
