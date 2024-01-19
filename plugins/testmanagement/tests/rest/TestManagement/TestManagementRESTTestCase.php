<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\TestManagement;

use Psr\Http\Message\ResponseInterface;
use RestBase;
use Tuleap\TestManagement\Tests\Rest\Cache;

/**
 * @group TestManagementTest
 */
abstract class TestManagementRESTTestCase extends RestBase
{
    protected $project_id;
    protected $project_with_attachment_id;
    protected $closed_71_campaign = null;
    protected $valid_73_campaign  = null;
    protected $valid_130_campaign = null;

    /**
     * @var Cache
     */
    private $ttm_cache;

    protected function getResponse($request, $user_name = TestManagementDataBuilder::USER_TESTER_NAME): ResponseInterface
    {
        return parent::getResponse($request, $user_name);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->ttm_cache = Cache::instance();

        if ($this->project_id === null) {
            $this->project_id = $this->getProjectId(TestManagementDataBuilder::PROJECT_TEST_MGMT_SHORTNAME);
        }

        if ($this->project_with_attachment_id === null) {
            $this->project_with_attachment_id = $this->getProjectId(TestManagementDataBuilder::PROJECT_TEST_MGMT_WITH_ATTACHMENTS_SHORTNAME);
        }

        $this->valid_73_campaign = $this->ttm_cache->getValidCampaign();
        if ($this->valid_73_campaign === null) {
            $this->valid_73_campaign = $this->getValid73Campaign();
            $this->ttm_cache->setValidCampaign($this->valid_73_campaign);
        }

        $this->closed_71_campaign = $this->ttm_cache->getClosedCampaign();
        if ($this->closed_71_campaign === null) {
            $this->closed_71_campaign = $this->getClosed71Campaign();
            $this->ttm_cache->setClosedCampaign($this->closed_71_campaign);
        }

        $this->valid_130_campaign = $this->ttm_cache->getValidWithAttachmentsCampaign();
        if ($this->valid_130_campaign === null) {
            $this->valid_130_campaign = $this->getValid130Campaign();
            $this->ttm_cache->setValidWithAttachmentsCampaign($this->valid_130_campaign);
        }
    }

    private function getValid73Campaign()
    {
        $all_campaigns_request  = $this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_campaigns");
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns              = json_decode($all_campaigns_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $index_of_valid73_when_sorted_by_id = 0;
        $campaign                           = $campaigns[$index_of_valid73_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.3');
        $this->assertTrue($campaign['user_can_close']);
        $this->assertTrue($campaign['user_can_open']);

        return $campaign;
    }

    private function getValid130Campaign()
    {
        $all_campaigns_request  = $this->request_factory->createRequest('GET', "projects/$this->project_with_attachment_id/testmanagement_campaigns");
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns              = json_decode($all_campaigns_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $index_of_valid130_when_sorted_by_id = 0;
        $campaign                            = $campaigns[$index_of_valid130_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 13.0');

        return $campaign;
    }

    private function getClosed71Campaign()
    {
        $all_campaigns_request  = $this->request_factory->createRequest('GET', "projects/$this->project_id/testmanagement_campaigns?" .
        http_build_query(['query' => '{"status":"closed"}']));
        $all_campaigns_response = $this->getResponse($all_campaigns_request);
        $campaigns              = json_decode($all_campaigns_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $index_of_valid71_when_sorted_by_id = 1;
        $campaign                           = $campaigns[$index_of_valid71_when_sorted_by_id];
        $this->assertEquals($campaign['label'], 'Tuleap 7.1');

        return $campaign;
    }
}
