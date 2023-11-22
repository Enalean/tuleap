<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2006
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

use Tuleap\Date\DateHelper;
use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemDetailsSectionHistory extends Docman_View_ItemDetailsSection
{
    public $logger;

    public $display_access_logs;

    public function __construct($item, $url, $display_access_logs, $logger)
    {
        parent::__construct($item, $url, 'history', dgettext('tuleap-docman', 'History'));
        $this->logger              = $logger;
        $this->display_access_logs = $display_access_logs;
    }

    public function getContent($params = [])
    {
        $content = '';

        $current_user = UserManager::instance()->getCurrentUser();

        if ($this->item instanceof Docman_File) {
            $content .= $this->getFileVersions($current_user);
        } elseif ($this->item instanceof Docman_Link) {
            $content .= $this->getLinkVersions($current_user);
        }

        if ($this->logger) {
            $content .= $this->logger->fetchLogsForItem($this->item, $this->display_access_logs, $current_user);
        }

        return $content;
    }

    private function getFileVersions(\PFUser $current_user): string
    {
        $uh              = UserHelper::instance();
        $content         = '<h3>' . dgettext('tuleap-docman', 'Versions') . '</h3>';
        $version_factory = new Docman_VersionFactory();
        $approvalFactory = Docman_ApprovalTableFactoriesFactory::getFromItem($this->item);
        $versions        = $version_factory->getAllVersionForItem($this->item);

        if ($versions) {
            if (count($versions)) {
                $titles   = [];
                $titles[] = dgettext('tuleap-docman', 'Version');
                $titles[] = dgettext('tuleap-docman', 'Date');
                $titles[] = dgettext('tuleap-docman', 'Author');
                $titles[] = dgettext('tuleap-docman', 'Version name');
                $titles[] = dgettext('tuleap-docman', 'Change Log');
                $titles[] = dgettext('tuleap-docman', 'Approval');
                $titles[] = dgettext('tuleap-docman', 'Delete');
                $content .= html_build_list_table_top($titles, false, false, false, null, "table");
                $odd_even = ['boxitem', 'boxitemalt'];
                $i        = 0;
                foreach ($versions as $key => $nop) {
                    $download       = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'show', 'id' => $this->item->getId(), 'version_number' => $versions[$key]->getNumber()]
                    );
                    $delete         = DocmanViewURLBuilder::buildActionUrl(
                        $this->item,
                        ['default_url' => $this->url],
                        ['action' => 'confirmDelete', 'id' => $this->item->getId(), 'version' => $versions[$key]->getNumber()]
                    );
                    $delete_version = "delete-" . $versions[$key]->getNumber();

                    $user     = $versions[$key]->getAuthorId() ? $uh->getDisplayNameFromUserId($versions[$key]->getAuthorId()) : dgettext('tuleap-docman', 'Anonymous');
                    $content .= '<tr class="' . $odd_even[$i++ % count($odd_even)] . '">';
                    $content .= '<td align="center"><a href="' . $download . '">' . $versions[$key]->getNumber() . '</a></td>';
                    $content .= '<td>' . DateHelper::relativeDateBlockContext($versions[$key]->getDate(), $current_user) . '</td>';
                    $content .= '<td>' . $this->hp->purify($user)                                                  . '</td>';
                    $content .= '<td>' . $this->hp->purify($versions[$key]->getLabel())         . '</td>';
                    $content .= '<td>' . $this->hp->purify($versions[$key]->getChangelog(), CODENDI_PURIFIER_LIGHT) . '</td>';

                    $table = $approvalFactory->getTableFromVersion($versions[$key]);
                    if ($table != null) {
                        $appTable = DocmanViewURLBuilder::buildActionUrl(
                            $this->item,
                            ['default_url' => $this->url],
                            [
                                'action'  => 'details',
                                'section' => 'approval',
                                'id'      => $this->item->getId(),
                                'version' => $versions[$key]->getNumber(),
                            ]
                        );
                        $content .= '<td align="center"><a href="' . $appTable . '">' . $titles[] = dgettext('tuleap-docman', 'Show') . '</a></td>';
                    } else {
                        $content .= '<td></td>';
                    }
                    $content .= '<td align="center"><a href="' . $delete . '" data-test="' . $delete_version . '"><img src="' . util_get_image_theme("ic/trash.png") . '" height="16" width="16" border="0"></a></td>';
                    $content .= '</tr>';
                }
                $content .= '</table>';
            } else {
                $content .= '<div>' . dgettext('tuleap-docman', 'There is no version yet') . '</div>';
            }
        } else {
            $content .= '<div>' . dgettext('tuleap-docman', 'Error while searching for old versions') . '</div>';
        }

        return $content;
    }

    private function getLinkVersions(\PFUser $current_user): string
    {
        $uh      = UserHelper::instance();
        $content = '<h3>' . dgettext('tuleap-docman', 'Versions') . '</h3>';

        $version_factory = new Docman_LinkVersionFactory();
        $versions        = $version_factory->getAllVersionForItem($this->item);

        if ($versions) {
            $titles   = [
                dgettext('tuleap-docman', 'Version'),
                dgettext('tuleap-docman', 'Date'),
                dgettext('tuleap-docman', 'Author'),
                dgettext('tuleap-docman', 'Label'),
                dgettext('tuleap-docman', 'Change Log'),
            ];
            $content .= html_build_list_table_top($titles, false, false, false, null, "table");

            $odd_even = ['boxitem', 'boxitemalt'];
            $i        = 0;

            foreach (array_keys($versions) as $key) {
                $download = DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'show', 'id' => $this->item->getId(), 'version_number' => $versions[$key]->getNumber()]
                );
                $user     = $versions[$key]->getAuthorId() ? $uh->getDisplayNameFromUserId($versions[$key]->getAuthorId()) : dgettext('tuleap-docman', 'Anonymous');
                $content .= '<tr class="' . $odd_even[$i++ % count($odd_even)] . '">';
                $content .= '<td align="center"><a href="' . $download . '">' . $versions[$key]->getNumber() . '</a></td>';
                $content .= '<td>' . DateHelper::relativeDateBlockContext($versions[$key]->getDate(), $current_user) . '</td>';
                $content .= '<td>' . $this->hp->purify($user) . '</td>';
                $content .= '<td>' . $this->hp->purify($versions[$key]->getLabel()) . '</td>';
                $content .= '<td>' . $this->hp->purify($versions[$key]->getChangelog(), CODENDI_PURIFIER_LIGHT) . '</td>';
                $content .= '</tr>';
            }
            $content .= '</table>';
        } else {
            $content .= '<div>' . dgettext('tuleap-docman', 'Error while searching for old versions') . '</div>';
        }

        return $content;
    }
}
