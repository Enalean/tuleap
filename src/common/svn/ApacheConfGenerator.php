<?php
/**
 * Copyright Enalean (c) 2016. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Svn;

use Backend;
use BackendSVN;
use Tuleap\System\ApacheServiceControl;
use Tuleap\System\ServiceControl;
use TuleapCfg\Command\ProcessFactory;

class ApacheConfGenerator
{
    /**
     * @var ApacheServiceControl
     */
    private $service_control;
    /**
     * @var BackendSVN
     */
    private $backend_svn;

    public function __construct(
        ApacheServiceControl $service_control,
        BackendSVN $backend_svn
    ) {
        $this->service_control = $service_control;
        $this->backend_svn     = $backend_svn;
    }

    public static function build()
    {
        return new self(new ApacheServiceControl(new ServiceControl(), new ProcessFactory()), Backend::instanceSVN());
    }

    public function generate()
    {
        $this->backend_svn->generateSVNApacheConf();
        $this->service_control->reload();
    }
}
