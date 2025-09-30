<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han <xiphux@gmail.com>
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

namespace Tuleap\Git\GitPHP;

use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyCategory;
use Tuleap\Config\ConfigKeyLegacyBool;
use Tuleap\User\ProvideCurrentUserWithLoggedInInformation;

#[ConfigKeyCategory('Git')]
class Controller_Snapshot extends ControllerBase // phpcs:ignore
{
    #[ConfigKey('Allow anonymous users to download snapshot archives of public Git repositories')]
    #[ConfigKeyLegacyBool(true)]
    public const string IS_ANONYMOUS_SNAPSHOT_DOWNLOAD_ALLOWED = 'git_anonymous_snapshot_download_allowed';

    /**
     * archive
     *
     * Stores the archive object
     *
     * @access private
     */
    private $archive = null;

    public function __construct()
    {
        $this->project = ProjectList::GetInstance()->GetProject();

        $this->ReadQuery();
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    #[\Override]
    protected function GetTemplate() // phpcs:ignore
    {
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param bool $local true if caller wants the localized action name
     * @return string action name
     */
    #[\Override]
    public function GetName($local = false) // phpcs:ignore
    {
        if ($local) {
            return dgettext('gitphp', 'snapshot');
        }
        return 'snapshot';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    #[\Override]
    protected function ReadQuery() // phpcs:ignore
    {
        if (isset($_GET['h'])) {
            $this->params['hash'] = $_GET['h'];
        }
        if (isset($_GET['f'])) {
            $this->params['path'] = $_GET['f'];
        }
        if (isset($_GET['prefix'])) {
            $this->params['prefix'] = $_GET['prefix'];
        }
        if (isset($_GET['fmt'])) {
            $this->params['format'] = $_GET['fmt'];
        } else {
            $this->params['format'] = Config::GetInstance()->GetValue('compressformat', Archive::COMPRESS_ZIP);
        }
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    #[\Override]
    protected function LoadHeaders() // phpcs:ignore
    {
        $this->archive = new Archive($this->project, null, $this->params['format'], (isset($this->params['path']) ? $this->params['path'] : ''), (isset($this->params['prefix']) ? $this->params['prefix'] : ''));

        switch ($this->archive->GetFormat()) {
            case Archive::COMPRESS_TAR:
                $this->headers[] = 'Content-Type: application/x-tar';
                break;
            case Archive::COMPRESS_BZ2:
                $this->headers[] = 'Content-Type: application/x-bzip2';
                break;
            case Archive::COMPRESS_GZ:
                $this->headers[] = 'Content-Type: application/x-gzip';
                break;
            case Archive::COMPRESS_ZIP:
                $this->headers[] = 'Content-Type: application/x-zip';
                break;
            default:
                throw new \Exception('Unknown compression type');
        }

        $this->headers[] = 'Content-Disposition: attachment; filename=' . $this->archive->GetFilename();
        $this->headers[] = 'X-Content-Type-Options: nosniff';
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    #[\Override]
    protected function LoadData() // phpcs:ignore
    {
        $commit = null;

        if (! isset($this->params['hash'])) {
            $commit = $this->project->GetHeadCommit();
        } else {
            $commit = $this->project->GetCommit($this->params['hash']);
        }

        if ($commit === null) {
            throw new NotFoundException();
        }

        $this->archive->SetObject($commit);
    }

    /**
     * Render
     *
     * Render this controller
     *
     * @access public
     */
    #[\Override]
    public function Render() // phpcs:ignore
    {
        $this->LoadData();

        if ($this->archive->Open()) {
            while (($data = $this->archive->Read()) !== false) {
                print $data;
                flush();
            }
            $this->archive->Close();
        }
    }

    public static function isSnapshotArchiveDownloadAllowed(ProvideCurrentUserWithLoggedInInformation $current_user_provider): bool
    {
        return \ForgeConfig::getStringAsBool(self::IS_ANONYMOUS_SNAPSHOT_DOWNLOAD_ALLOWED) ||
            $current_user_provider->getCurrentUserWithLoggedInInformation()->is_logged_in;
    }
}
