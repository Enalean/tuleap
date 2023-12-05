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

use Tuleap\SVNCore\Repository;
use Tuleap\SVNCore\SHA1CollisionDetector;
use Tuleap\SVNCore\SHA1CollisionException;

final class CollidingSHA1Validator implements PathValidator
{
    /**
     * @var Svnlook
     */
    private $svnlook;
    /**
     * @var SHA1CollisionDetector
     */
    private $sha1_collision_detector;

    public function __construct(Svnlook $svnlook, SHA1CollisionDetector $sha1_collision_detector)
    {
        $this->svnlook                 = $svnlook;
        $this->sha1_collision_detector = $sha1_collision_detector;
    }

    /**
     * @throws SHA1CollisionException
     * @throws \RuntimeException
     */
    public function assertPathIsValid(Repository $repository, string $transaction, string $path): void
    {
        $this->assertPathDoesNotContainSHA1Collision($repository, $transaction, $path);
    }

    /**
     * @throws SHA1CollisionException
     * @throws \RuntimeException
     */
    private function assertPathDoesNotContainSHA1Collision(Repository $repository, string $transaction, string $path): void
    {
        $matches = [];
        if ($this->extractFilenameFromNonDeletedPath($path, $matches)) {
            return;
        }
        $filename    = $matches[1];
        $handle_file = $this->svnlook->getContent($repository, $transaction, $filename);
        if ($handle_file === false) {
            throw new \RuntimeException("Can't get the content of the file $filename");
        }
        $is_colliding = $this->sha1_collision_detector->isColliding($handle_file);
        $this->svnlook->closeContentResource($handle_file);
        if ($is_colliding) {
            throw new SHA1CollisionException("Known SHA-1 collision rejected on file $filename");
        }
    }

    private function extractFilenameFromNonDeletedPath(string $path, array &$matches): bool
    {
        return preg_match('/^[^D]\s+(.*)$/', $path, $matches) !== 1;
    }
}
