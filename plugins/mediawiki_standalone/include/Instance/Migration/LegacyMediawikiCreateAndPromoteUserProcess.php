<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Project;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class LegacyMediawikiCreateAndPromoteUserProcess implements LegacyMediawikiCreateAndPromoteUser
{
    /**
     * @psalm-return Ok<null>|Err<Fault>
     */
    public function create(LoggerInterface $logger, Project $project, string $rev_user_text): Ok|Err
    {
        try {
            $logger->info(self::class . ' ' . $rev_user_text);
            $process = new Process(['/usr/share/tuleap/plugins/mediawiki/bin/mw-maintenance-wrapper.php', $project->getUnixNameLowerCase(), 'createAndPromote.php', $rev_user_text, \bin2hex(\random_bytes(20))], null, ['DISPLAY_ERRORS' => 'true']);
            $process->setTimeout(0);
            $process->mustRun();
            $logger->debug($process->getOutput());
            if ($process->isSuccessful()) {
                return Result::ok(null);
            }
            $logger->error($process->getErrorOutput());
            return Result::err(Fault::fromMessage('Error with createAndPromote script: ' . $process->getErrorOutput()));
        } catch (ProcessFailedException $exception) {
            return Result::err(Fault::fromThrowableWithMessage($exception, 'Adding missing mediawiki user command failed'));
        } catch (\Exception $exception) {
            return Result::err(Fault::fromThrowableWithMessage($exception, 'Adding missing mediawiki user failure'));
        }
    }
}
