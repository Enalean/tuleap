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

namespace Tuleap\Docman\Upload;

use Tuleap\Docman\Tus\TusEvent;
use Tuleap\Docman\Tus\TusEventSubscriber;

final class DocumentUploaded implements TusEventSubscriber
{
    /**
     * @var \Logger
     */
    private $logger;

    public function __construct(\Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getInterestedBySubject()
    {
        return TusEvent::UPLOAD_COMPLETED;
    }

    /**
     * @return void
     */
    public function notify(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $this->logger->debug('Document has been uploaded');
        // Do the stuff that needs to be done when a document is added to the document manager
    }
}
