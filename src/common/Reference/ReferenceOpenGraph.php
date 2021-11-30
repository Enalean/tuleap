<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Reference;

use Codendi_HTMLPurifier;
use Embed\Embed;
use Exception;
use Reference;

class ReferenceOpenGraph
{

    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;
    /**
     * @var Reference
     */
    private $reference;
    private Embed $embed;

    public function __construct(
        Codendi_HTMLPurifier $purifier,
        Reference $reference,
        Embed $embed,
    ) {
        $this->purifier  = $purifier;
        $this->reference = $reference;
        $this->embed     = $embed;
    }

    public function getContent(): string
    {
        $html = '';
        try {
            if (! $this->isHTTPUrl($this->reference->getLink())) {
                return '';
            }

            $embed_extractor = $this->embed->get($this->reference->getLink());
            if ($embed_extractor->title) {
                $html .= $this->purifier->purify($embed_extractor->title);
            }
            if ($embed_extractor->description) {
                if ($embed_extractor->title) {
                    $html .= "<br />";
                }
                $html .= $this->purifier->purify($embed_extractor->description);
            }
        } catch (Exception $exception) {
            // Skip invalid URLs
        }
        return $html;
    }

    private function isHTTPUrl(string $url): bool
    {
        return strpos(strtolower($url), 'http') !== false;
    }
}
