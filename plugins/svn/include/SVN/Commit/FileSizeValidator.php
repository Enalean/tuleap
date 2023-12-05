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

use ForgeConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Tuleap\Config\ConfigKey;
use Tuleap\SVNCore\Repository;

final class FileSizeValidator implements PathValidator
{
    #[ConfigKey("Define the maximum file size for new files committed to Subversion plugin (in bytes)")]
    public const CONFIG_KEY = 'plugin_svn_file_size_limit';

    private const SVNLOOK_ERROR_IS_NOT_FILE = 'svnlook: E160017:';

    /**
     * @var Svnlook
     */
    private $svnlook;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Svnlook $svnlook, LoggerInterface $logger)
    {
        $this->svnlook = $svnlook;
        $this->logger  = $logger;
    }

    public function assertPathIsValid(Repository $repository, string $transaction, string $path): void
    {
        try {
            if (! self::isLimitSet()) {
                return;
            }
            $filename = $this->getFilenameFromNonDeletedPath($path);
            if ($filename === null) {
                return;
            }
            $size = $this->svnlook->getFileSize($repository, $transaction, $filename);
            $this->logger->debug("Computed size for $filename: $size");
            $limit = $this->getLimitInBytes();
            if ($size > $limit) {
                throw new CommittedFileTooLargeException($filename, $size, $limit);
            }
        } catch (ProcessFailedException $exception) {
            if (str_starts_with($exception->getProcess()->getErrorOutput(), self::SVNLOOK_ERROR_IS_NOT_FILE)) {
                return;
            }
            $this->logger->error($exception->getProcess()->getErrorOutput());
            throw $exception;
        }
    }

    private function getLimitInBytes(): int
    {
        return ForgeConfig::getInt(self::CONFIG_KEY) * 1024 * 1024;
    }

    public static function isLimitSet(): bool
    {
        return ForgeConfig::getInt(self::CONFIG_KEY, 0) !== 0;
    }

    private function getFilenameFromNonDeletedPath(string $path): ?string
    {
        $matches = [];
        if (preg_match('/^[^D]\s+(.*)$/', $path, $matches) === 1) {
            return $matches[1];
        }
        return null;
    }
}
