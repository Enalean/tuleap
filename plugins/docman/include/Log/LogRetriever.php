<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Log;

use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElementFactory;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\User\RetrieveUserById;

final class LogRetriever
{
    public function __construct(
        private IRetrieveStoredLog $dao,
        private RetrieveUserById $user_retriever,
        private Docman_MetadataListOfValuesElementFactory $love_factory,
    ) {
    }

    public function getPaginatedLogForItem(\Docman_Item $item, int $limit, int $offset, bool $display_access_logs): LogEntryPage
    {
        $item_id = (int) $item->getId();
        $total   = $this->dao->countByItemId($item_id);
        if ($total <= 0) {
            return LogEntryPage::noLog();
        }

        return LogEntryPage::page(
            $total,
            $this->instantiateEntries(
                $item,
                $this->dao->paginatedSearchByItemIdOrderByTimestamp($item_id, $limit, $offset),
                $display_access_logs
            )
        );
    }

    /**
     * @return LogEntry[]
     */
    private function instantiateEntries(\Docman_Item $item, array $rows, bool $display_access_logs): array
    {
        $entries = [];
        foreach ($rows as $row) {
            if ((int) $row['type'] === LogEntry::EVENT_ACCESS && ! $display_access_logs) {
                continue;
            }

            $when = (new \DateTimeImmutable())->setTimestamp((int) $row['time']);
            $who  = $this->user_retriever->getUserById((int) $row['user_id']);
            if (! $who) {
                continue;
            }

            $type      = (int) $row['type'];
            $what      = $this->getText($type);
            $old_value = null;
            $new_value = null;
            $diff_link = null;
            switch ($type) {
                case LogEntry::EVENT_METADATA_UPDATE:
                    $old_value = $row['old_value'];
                    $new_value = $row['new_value'];

                    $mdFactory = new Docman_MetadataFactory($row['group_id']);
                    try {
                        $metadata = $mdFactory->getMetadataFromLabel($row['field']);
                        if ($metadata === null) {
                            continue 2;
                        }

                        if ($metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                            $old_love = $this->love_factory->getByElementId($row['old_value'], $metadata->getLabel());
                            $new_love = $this->love_factory->getByElementId($row['new_value'], $metadata->getLabel());
                            if ($old_love !== null) {
                                $old_value = $old_love->getName();
                            }
                            if ($new_love !== null) {
                                $new_value = $new_love->getName();
                            }
                        } else {
                            if ($metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_DATE) {
                                $old_value = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $old_value);
                                $new_value = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $new_value);
                            }
                        }
                        $what = sprintf(
                            dgettext('tuleap-docman', 'Change "%1$s"'),
                            $metadata->getName()
                        );
                    } catch (CustomMetadataException $e) {
                        continue 2;
                    }
                    break;

                case LogEntry::EVENT_WIKIPAGE_UPDATE:
                    if (! $item instanceof \Docman_Wiki) {
                        continue 2;
                    }
                    $old_version = $row['old_value'];
                    $new_version = $row['new_value'];

                    $pagename   = $item->getPageName();
                    $diff_link  = '/wiki/index.php?group_id=' . $row['group_id'];
                    $diff_link .= '&pagename=' . urlencode($pagename) . '&action=diff';
                    $diff_link .= '&versions%5b%5d=' . $old_version . '&versions%5b%5d=' . $new_version;
                    break;

                case LogEntry::EVENT_SET_VERSION_AUTHOR:
                    $new_value = $row['new_value'];
                    break;

                case LogEntry::EVENT_SET_VERSION_DATE:
                    $new_value = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['new_value']);
                    break;

                case LogEntry::EVENT_DEL_VERSION:
                case LogEntry::EVENT_RESTORE_VERSION:
                    $old_value = $row['old_value'];
                    break;

                default:
                    break;
            }

            $entries[] = new LogEntry(
                $when,
                $who,
                $what,
                $old_value,
                $new_value,
                $diff_link,
                $type,
                $row['field'],
                $row['group_id']
            );
        }

        return $entries;
    }

    private function getText(int $type): string
    {
        $txt = '';
        switch ($type) {
            case LogEntry::EVENT_ADD:
                $txt = dgettext('tuleap-docman', 'Create');
                break;
            case LogEntry::EVENT_EDIT:
                $txt = dgettext('tuleap-docman', 'Edit');
                break;
            case LogEntry::EVENT_MOVE:
                $txt = dgettext('tuleap-docman', 'Move');
                break;
            case LogEntry::EVENT_DEL:
                $txt = dgettext('tuleap-docman', 'Delete');
                break;
            case LogEntry::EVENT_DEL_VERSION:
                $txt = dgettext('tuleap-docman', 'Delete version');
                break;
            case LogEntry::EVENT_ACCESS:
                $txt = dgettext('tuleap-docman', 'Access');
                break;
            case LogEntry::EVENT_NEW_VERSION:
                $txt = dgettext('tuleap-docman', 'New version');
                break;
            case LogEntry::EVENT_METADATA_UPDATE:
                $txt = dgettext('tuleap-docman', 'Property change');
                break;
            case LogEntry::EVENT_WIKIPAGE_UPDATE:
                $txt = dgettext('tuleap-docman', 'Wiki page content change');
                break;
            case LogEntry::EVENT_SET_VERSION_AUTHOR:
                $txt = dgettext('tuleap-docman', 'Version author');
                break;
            case LogEntry::EVENT_SET_VERSION_DATE:
                $txt = dgettext('tuleap-docman', 'Version date');
                break;
            case LogEntry::EVENT_RESTORE:
                $txt = dgettext('tuleap-docman', 'Restore');
                break;
            case LogEntry::EVENT_RESTORE_VERSION:
                $txt = dgettext('tuleap-docman', 'Restore version');
                break;
            case LogEntry::EVENT_LOCK_ADD:
                $txt = dgettext('tuleap-docman', 'Locked document');
                break;
            case LogEntry::EVENT_LOCK_DEL:
                $txt = dgettext('tuleap-docman', 'Released lock');
                break;
            default:
                break;
        }

        return $txt;
    }
}
