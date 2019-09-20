<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Cardwall\AccentColor\AccentColor;
use Tuleap\Cardwall\BackgroundColor\BackgroundColor;

require_once dirname(__FILE__) .'/bootstrap.php';
require_once 'common/TreeNode/TreeNodeMapper.class.php';

class Cardwall_ArtifactNodeTreeProvider4Tests extends Cardwall_RendererBoardBuilder
{
    public function getCards(array $artifact_ids, $swimline_id)
    {
        return parent::getCards($artifact_ids, $swimline_id);
    }

    public function wrapInAThreeLevelArtifactTree(array $cards, $swimline_id)
    {
        return parent::wrapInAThreeLevelArtifactTree($cards, $swimline_id);
    }
}

class Cardwall_ArtifactNodeTreeProviderTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->node_factory     = mock('Cardwall_CardInCellPresenterNodeFactory');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->provider = new Cardwall_ArtifactNodeTreeProvider4Tests($this->node_factory, $this->artifact_factory);
    }

    public function itCreatesTwoLevelsEvenIfNoArtifactIdsAreGiven()
    {
        $root_node = $this->provider->wrapInAThreeLevelArtifactTree(array(), 'whatever');

        $this->assertTrue($root_node->hasChildren());
        $this->assertFalse($root_node->getChild(0)->hasChildren());
    }

    public function itHasASwimlineId()
    {
        $root_node = $this->provider->wrapInAThreeLevelArtifactTree(array(), 'Dat Id');

        $this->assertEqual($root_node->getChild(0)->getId(), 'Dat Id');
    }

    public function itCreatesAThreeLevelTreeBecauseItMustLookLikeTheNodeTreeFromAMilestone()
    {
        $artifact4 = aMockArtifact()->withId(4)->build();

        $root_node = $this->provider->wrapInAThreeLevelArtifactTree(array(new ArtifactNode($artifact4)), 'whatever');

        $this->assertTrue($root_node->hasChildren());
        $this->assertTrue($root_node->getChild(0)->hasChildren());
        $cards = $root_node->getChild(0)->getChildren();

        $this->assertEqual(1, count($cards));
        $card = $cards[0];
        $id   = $card->getId();
        $this->assertEqual($id, 4);
        $artifact = $card->getArtifact();
        $this->assertIdentical($artifact, $artifact4);
    }

    public function testItCreatesAnArtifactNodeForEveryArtifactId()
    {
        $swmiline_id = 7;

        $artifact4 = aMockArtifact()->withId(4)->build();
        $artifact5 = aMockArtifact()->withId(5)->build();
        $artifact6 = aMockArtifact()->withId(6)->build();

        $node4 = new Cardwall_CardInCellPresenterNode(
            new Cardwall_CardInCellPresenter(
                new Cardwall_CardPresenter(
                    \Mockery::spy(PFUser::class),
                    $artifact4,
                    mock('Cardwall_CardFields'),
                    mock(AccentColor::class),
                    mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'),
                    0,
                    array(),
                    mock(BackgroundColor::class)
                ),
                4
            )
        );
        $node5 = new Cardwall_CardInCellPresenterNode(
            new Cardwall_CardInCellPresenter(
                new Cardwall_CardPresenter(
                    \Mockery::spy(PFUser::class),
                    $artifact5,
                    mock('Cardwall_CardFields'),
                    mock(AccentColor::class),
                    mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'),
                    0,
                    array(),
                    mock(BackgroundColor::class)
                ),
                5
            )
        );
        $node6 = new Cardwall_CardInCellPresenterNode(
            new Cardwall_CardInCellPresenter(
                new Cardwall_CardPresenter(
                    \Mockery::spy(PFUser::class),
                    $artifact6,
                    mock('Cardwall_CardFields'),
                    mock(AccentColor::class),
                    mock('Cardwall_UserPreferences_UserPreferencesDisplayUser'),
                    0,
                    array(),
                    mock(BackgroundColor::class)
                ),
                6
            )
        );

        stub($this->artifact_factory)->getArtifactById(4)->returns($artifact4);
        stub($this->artifact_factory)->getArtifactById(5)->returns($artifact5);
        stub($this->artifact_factory)->getArtifactById(6)->returns($artifact6);

        stub($this->node_factory)->getCardInCellPresenterNode($artifact4, $swmiline_id)->returns($node4);
        stub($this->node_factory)->getCardInCellPresenterNode($artifact5, $swmiline_id)->returns($node5);
        stub($this->node_factory)->getCardInCellPresenterNode($artifact6, $swmiline_id)->returns($node6);

        $cards = $this->provider->getCards(array(4, 5, 6), $swmiline_id);

        $this->assertEqual(3, count($cards));
        foreach ($cards as $card) {
            $id = $card->getId();
            $this->assertBetweenClosedInterval($id, 4, 6);
            $artifact = $card->getArtifact();
            $this->assertBetweenClosedInterval($artifact->getId(), 4, 6);
            $this->assertIsA($artifact, 'Tracker_Artifact');
        }
    }
}
