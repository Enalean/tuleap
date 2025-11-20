<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use Docman_ItemFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Docman\REST\v1\DocmanItemsRequestBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use UserManager;

final class NotificationsStatisticsController extends DispatchablePSR15Compatible
{
    public function __construct(
        private DocmanItemsRequestBuilder $item_request_builder,
        private UserManager $user_manager,
        private JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $item_id = $request->getAttribute('item_id');

        $items_request = $this->item_request_builder->buildFromItemId($item_id);
        $item          = $items_request->getItem();
        $project       = $items_request->getProject();
        $current_user  = $this->user_manager->getCurrentUser();
        $item_factory  = Docman_ItemFactory::instance((int) $project->getID());
        $folder_stats  = $item_factory->getFolderStats($item, $current_user);

        $types = [];
        foreach ($folder_stats['types'] as $type_name => $count) {
            switch (strtolower($type_name)) {
                case 'file':
                    $label = dgettext('tuleap-docman', 'Files');
                    break;
                case 'wiki':
                    $label = dgettext('tuleap-docman', 'Wiki pages');
                    break;
                case 'embeddedfile':
                    $label = dgettext('tuleap-docman', 'Embedded files');
                    break;
                case 'empty':
                    $label = dgettext('tuleap-docman', 'Empty documents');
                    break;
                case 'link':
                    $label = dgettext('tuleap-docman', 'Links');
                    break;
                case 'folder':
                    $label = dgettext('tuleap-docman', 'Folders');
                    break;
                default:
                    $label = dgettext('tuleap-docman', 'Unknown item type');
            }
            $types[] = ['type_name' => $label, 'count' => $count];
        }

        return $this->json_response_builder->fromData([
            'size' => $this->convertBytesToHumanReadable($folder_stats['size']),
            'count' => $folder_stats['count'],
            'types' => $types,
        ])->withStatus(200);
    }

    private function convertBytesToHumanReadable(float $bytes): string
    {
        $s = ['', 'k', 'M', 'G', 'T', 'P'];

        if ($bytes > 0) {
            $e = (int) floor(log($bytes) / log(1024));
            if ($e > 5) {
                $e = 5;
            }
            if ($e < 0) {
                $e = 0;
            }
            $displayed_size = round($bytes / pow(1024, floor($e)), 2);
        } else {
            $e              = 0;
            $displayed_size = 0;
        }

        return (string) $displayed_size . ' ' . $s[$e] . 'B';
    }
}
