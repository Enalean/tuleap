<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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


class Cardwall_CardController extends MVC2_PluginController
{

    /** @var Cardwall_SingleCard */
    private $single_card;

    public function __construct(
        Codendi_Request $request,
        Cardwall_SingleCard $single_card
    ) {
        parent::__construct('cardwall', $request);
        $this->single_card = $single_card;
    }

    public function getCard()
    {
        $card_in_cell_presenter = $this->single_card->getCardInCellPresenter();
        $artifact_id            = $card_in_cell_presenter->getArtifact()->getId();
        $card_presenter         = $card_in_cell_presenter->getCardPresenter();

        $json_format = [
            $artifact_id => [
                'title'        => $card_presenter->getTitle(),
                'xref'         => $card_presenter->getXRef(),
                'edit_url'     => $card_presenter->getEditUrl(),
                'accent_color' => $card_presenter->getAccentColor(),
                'column_id'    => $this->single_card->getColumnId(),
                'drop_into'    => $card_in_cell_presenter->getDropIntoIds(),
                'fields'       => [],
                'html_fields'  => [],
            ],
        ];
        foreach ($this->single_card->getFields() as $field) {
            $this->addJsonFieldValues($json_format[$artifact_id], $field);
            $this->addHTMLFieldValues($json_format[$artifact_id], $field);
        }

        $GLOBALS['Response']->sendJSON($json_format);
    }

    private function addJsonFieldValues(&$json_format, $field)
    {
        $json_format['fields'][$field->getName()] = $this->single_card->getFieldJsonValue($this->request->getCurrentUser(), $field);
    }

    private function addHTMLFieldValues(&$json_format, $field)
    {
        $json_format['html_fields'][$field->getName()] = $this->single_card->getFieldHTMLValue($this->request->getCurrentUser(), $field);
    }
}
