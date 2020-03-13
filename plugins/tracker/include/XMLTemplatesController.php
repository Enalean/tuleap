<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use XML_Security;

class XMLTemplatesController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $layout->header(['title' => 'Tracker templates']);
        echo '<ul>';
        foreach (new \DirectoryIterator(__DIR__ . '/../www/resources/templates/') as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() === 'xml') {
                $xml_security = new XML_Security();
                $xml = $xml_security->loadFile($file->getPathname());
                echo '<li><p>';
                echo '<strong><a href="/plugins/tracker/resources/templates/' . $file->getBasename() . '">' . $xml->name . '</a></strong><br/>';
                echo '' . $xml->description . '</p>';
                echo '</li>';
            }
        }
        echo '</ul>';
        $layout->footer([]);
    }
}
