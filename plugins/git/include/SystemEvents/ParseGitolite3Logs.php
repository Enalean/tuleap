<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\SystemEvents;

use SystemEvent;
use Tuleap\Git\Gitolite\Gitolite3LogParser;

class ParseGitolite3Logs extends SystemEvent
{
    const NAME = 'GIT_PARSE_GITOLITE3_LOGS';

    /** @var Gitolite3LogParser */
    private $gitolite_parser;

    public function injectDependencies(Gitolite3LogParser $gitolite_parser)
    {
        $this->gitolite_parser = $gitolite_parser;
    }

    public function process()
    {
        $this->gitolite_parser->parseCurrentAndPreviousMonthLogs(GITOLITE3_LOGS_PATH);
        $this->done();

        return true;
    }

    public function verbalizeParameters($with_link)
    {
        return '-';
    }
}
