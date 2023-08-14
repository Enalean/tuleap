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

namespace Tuleap\Log;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\ServerHostname;

final class TuleapIdentifierProcessor implements ProcessorInterface
{
    /**
     * @var VersionPresenter
     */
    private $version_presenter;

    public function __construct(VersionPresenter $version_presenter)
    {
        $this->version_presenter = $version_presenter;
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record['extra']['tuleap_version_number'] = $this->version_presenter->version_number;
        $record['extra']['tuleap_server']         = ServerHostname::hostnameWithHTTPSPort();
        return $record;
    }
}
