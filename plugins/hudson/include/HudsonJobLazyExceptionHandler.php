<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Hudson;

class HudsonJobLazyExceptionHandler
{
    /**
     * @var \HudsonJob|null
     */
    private $hudson_job;
    /**
     * @var \Exception|null
     */
    private $exception;

    public function __construct(?\HudsonJob $hudson_job = null, ?\Exception $exception = null)
    {
        $this->hudson_job = $hudson_job;
        $this->exception  = $exception;
    }

    /**
     * @return \HudsonJob
     * @throws \Exception
     */
    public function getHudsonJob()
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->hudson_job === null) {
            throw new \RuntimeException('HudsonJob object is null but no error have been provided, please check the initialization');
        }

        return $this->hudson_job;
    }
}
