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

/**
 * GitPHP Controller Snapshot
 *
 * Controller for getting a snapshot
 *
 */
/**
 * Snapshot controller class
 *
 */
class Controller_Snapshot extends ControllerBase // @codingStandardsIgnoreLine
{

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
    protected function GetTemplate() // @codingStandardsIgnoreLine
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
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        if ($local) {
            return dgettext("gitphp", 'snapshot');
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
    protected function ReadQuery() // @codingStandardsIgnoreLine
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
    protected function LoadHeaders() // @codingStandardsIgnoreLine
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
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        $commit = null;

        if (!isset($this->params['hash'])) {
            $commit = $this->project->GetHeadCommit();
        } else {
            $commit = $this->project->GetCommit($this->params['hash']);
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
    public function Render() // @codingStandardsIgnoreLine
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
}
