<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Widget;

use Codendi_Request;
use RuntimeException;
use Tracker_Report_Renderer;
use Tuleap\Widget\Event\ConfigureAtXMLImport;

class ProjectRendererWidgetXMLImporter
{
    private const RENDERER_PREFERENCE_NAME   = 'renderer';
    private const RENDERER_ID_REFERENCE_NAME = 'id';
    private const RENDERER_TITLE_VALUE_NAME  = 'title';
    private const RENDERER_ID_REQUEST_KEY    = 'renderer_id';
    private const RENDERER_TITLE_REQUEST_KEY = 'title';

    public function import(ConfigureAtXMLImport $event)
    {
        $request_params = $this->getDefaultRequestParameters();
        $xml            = $event->getXML();

        if (isset($xml->preference)) {
            foreach ($xml->preference as $preference) {
                $preference_name = trim((string) $xml->preference['name']);
                if ($preference_name !== self::RENDERER_PREFERENCE_NAME) {
                    continue;
                }

                foreach ($preference->reference as $reference) {
                    $key = trim((string) $reference['name']);
                    if ($key !== self::RENDERER_ID_REFERENCE_NAME) {
                        continue;
                    }

                    $request_params[self::RENDERER_PREFERENCE_NAME][self::RENDERER_ID_REQUEST_KEY] = $this->getRendererId($event, $reference);
                }

                foreach ($preference->value as $value) {
                    $key = trim((string) $value['name']);
                    if ($key !== self::RENDERER_TITLE_VALUE_NAME) {
                        continue;
                    }

                    $value = trim((string) $value);
                    $request_params[self::RENDERER_PREFERENCE_NAME][self::RENDERER_TITLE_REQUEST_KEY] = $value;
                }
            }
        }

        $content_id = $event->getWidget()->create(new Codendi_Request($request_params));
        $event->setContentId($content_id);
        $event->setWidgetIsConfigured();
    }

    private function getDefaultRequestParameters(): array
    {
        return [self::RENDERER_PREFERENCE_NAME => [
            self::RENDERER_TITLE_REQUEST_KEY => null,
            self::RENDERER_ID_REQUEST_KEY    => null
        ]];
    }

    /**
     * @throws RuntimeException
     */
    private function getRendererId(ConfigureAtXMLImport $event, $reference): int
    {
        $ref      = trim((string) $reference['REF']);
        $renderer = $event->getMappingsRegistry()->getReference($ref);
        assert($renderer instanceof Tracker_Report_Renderer);
        if ($renderer === null) {
            throw new RuntimeException("Reference $ref for tracker renderer widget was not found");
        }
        return (int) $renderer->getId();
    }
}
