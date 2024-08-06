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
                            'value' => $config['enable_ticket_status_counter'],
                        ]
                    ]
                ]
            ]
        ];
        renderTwigForm($form);
     }
 }
