<?php
/**
 * ---------------------------------------------------------------------
 * ITSM-NG
 * Copyright (C) 2022 ITSM-NG and contributors.
 *
 * https://www.itsm-ng.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of ITSM-NG.
 *
 * ITSM-NG is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ITSM-NG is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ITSM-NG. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

 class PluginFormcreatorConfig extends CommonDBTM {

    public function updateConfig($values) {
        global $DB;

        $configValues = $this->getConfig();

        foreach ($configValues as $key => $value) {
            if (isset($values[$key])) {
                $DB->query("UPDATE `glpi_plugin_formcreator_configs` SET `value` = '{$values[$key]}' WHERE `name` = '{$key}'");
            }
        }
    }
    
    public function getConfig() {
        global $DB;

        $query = "SELECT * FROM `glpi_plugin_formcreator_configs`";
        $result = iterator_to_array($DB->query($query));
        $config = [];
        foreach ($result as $row) {
            $config[$row['name']] = $row['value'];
        }
        return $config;
    }

    public function showConfigForm() {
        $config = $this->getConfig();

<<<<<<< HEAD
        $headerGeneral = __('General setup');
        $headerSimplified = __('Simplified view', 'formcreator');

        $profileLabel = __('Enable profile infos', 'formcreator');
        $pofileChecked = $config['enable_profile_info'] == 1 ? 'checked' : '';

        $collapseLabel = __('Collapse menu by default', 'formcreator');
        $collapseChecked = $config['collapse_menu'] == 1 ? 'checked' : '';

        $defaultCategoryLabel = __('Default category', 'formcreator');
        $defaultCategory = $config['default_categories_id'];

        ob_start();
        PluginFormcreatorCategory::dropdown([
            'name' => 'default_categories_id',
            'value' => $defaultCategory,
            'condition' => ['level' => 1]
        ]);
        $defaultCategoryDropdown = ob_get_clean();

        $seeAllLabel = __('Enable See all tab', 'formcreator');
        $seeAll = $config['see_all'] == 1 ? 'checked' : '';

        $savedSearchLabel = __('Saved searches');
        $savedSearch = $config['enable_saved_search'] == 1 ? 'checked' : '';

        $action = Plugin::getWebDir('formcreator') . '/front/config.form.php';

        $updateLabel = __('Update');

        $counterLabel = __('Enable ticket status counter', 'formcreator');
        $counterCheck = $config['enable_ticket_status_counter'] == 1 ? 'checked' : '';

        echo <<<HTML
        <div class="center vertical ui-tabs">
            <form action="$action" method="post">
            <table class="tab_cadre_fixe">
                <tbody>
                    <tr>
                        <th colspan="4">$headerGeneral</th>
                    </tr>
                    <tr>
                        <td>$seeAllLabel</td>
                        <td>
                            <input type="hidden" name="see_all" value="0">
                            <input type="checkbox" name="see_all" value="1" {$seeAll}/>
                        </td>
                        <td>$defaultCategoryLabel</td>
                        <td>$defaultCategoryDropdown</td>
                    </tr>
                    <tr>
                        <th colspan="4">$headerSimplified</th>
                    </tr>
                    <tr>
                        <td>$profileLabel</td>
                        <td>
                            <input type="hidden" name="enable_profile_info" value="0">
                            <input type="checkbox" name="enable_profile_info" value="1" {$pofileChecked}/>
                        </td>
                        <td>$savedSearchLabel</td>
                        <td>
                            <input type="hidden" name="enable_saved_search" value="0">
                            <input type="checkbox" name="enable_saved_search" value="1" {$savedSearch}/>
                        </td>
                    </tr>
                    <tr>
                        <td>$collapseLabel</td>
                        <td>
                            <input type="hidden" name="collapse_menu" value="0">
                            <input type="checkbox" name="collapse_menu" value="1" {$collapseChecked}/>
                        </td>
                        <td>$counterLabel</td>
                        <td>
                            <input type="hidden" name="enable_ticket_status_counter" value="0">
                            <input type="checkbox" name="enable_ticket_status_counter" value="1" {$counterCheck}/>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="center">
                <input type="submit" name="update" value="$updateLabel" class="submit">
            </div>
        HTML;
        Html::closeForm();
        echo '</div>';
=======
        $form = [
            'action' => Plugin::getWebDir('formcreator') . '/front/config.form.php',
            'buttons' => [
                'update' => [
                    'name' => 'update',
                    'value' => __('Update'),
                    'class' => 'btn btn-secondary'
                ]
            ],
            'content' => [
                __('General setup') => [
                    'visible' => true,
                    'inputs' => [
                        __('Enable See all tab', 'formcreator') => [
                            'type' => 'checkbox',
                            'name' => 'see_all',
                            'value' => $config['see_all'],
                        ],
                        __('Default category', 'formcreator') => [
                            'type' => 'select',
                            'name' => 'default_categories_id',
                            'values' => getOptionForItems(PluginFormcreatorCategory::class, ['level' => 1]),
                            'value' => $config['default_categories_id'],
                        ],
                    ]
                ],
                __('Simplified view', 'formcreator') => [
                    'visible' => true,
                    'inputs' => [
                        __('Enable profile infos', 'formcreator') => [
                            'type' => 'checkbox',
                            'name' => 'enable_profile_info',
                            'value' => $config['enable_profile_info'],
                        ],
                        __('Saved searches') => [
                            'type' => 'checkbox',
                            'name' => 'enable_saved_search',
                            'value' => $config['enable_saved_search'],
                        ],
                        __('Collapse menu by default', 'formcreator') => [
                            'type' => 'checkbox',
                            'name' => 'collapse_menu',
                            'value' => $config['collapse_menu'],
                        ],
                        __("Enable ticket status counter", 'formcreator') => [
                            'type' => 'checkbox',
                            'name' => 'enable_ticket_status_counter',
                        ]
                    ]
                ]
            ]
        ];
        renderTwigForm($form);
>>>>>>> latest
     }
 }