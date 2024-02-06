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

        $headerGeneral = __('General setup', 'formcreator');
        $headerSimplified = __('Simplified view', 'formcreator');

        $profileLabel = __('Enable profile infos', 'formcreator');
        $pofileChecked = $config['enable_profile_info'] == 1 ? 'checked' : '';

        $collapseLabel = __('Collapse menu by default', 'formcreator');
        $collapseChecked = $config['collapse_menu'] == 1 ? 'checked' : '';

        $defaultCategoryLabel = __('Default category', 'formcreator');
        $defaultCategory = $config['default_categories_id'];

        ob_start();
        PluginFormcreatorCategory::dropdown(['name' => 'default_categories_id', 'value' => $defaultCategory]);
        $defaultCategoryDropdown = ob_get_clean();

        $seeAllLabel = __('See all', 'formcreator');
        $seeAll = $config['see_all'] == 1 ? 'checked' : '';

        $action = Plugin::getWebDir('formcreator') . '/front/config.form.php';

        $updateLabel = __('Update');

        echo <<<HTML
        <div class="center vertical ui-tabs">
            <form action="$action" method="post">
            <table class="tab_cadre_fixe">
                <tbody>
                    <tr>
                        <th colspan="4">$headerGeneral</th>
                    </tr>
                    <tr>
                        <td>$profileLabel</td>
                        <td>
                            <input type="hidden" name="enable_profile_info" value="0">
                            <input type="checkbox" name="enable_profile_info" value="1" {$pofileChecked}/>
                        </td>
                        <td>$defaultCategoryLabel</td>
                        <td>$defaultCategoryDropdown</td>
                    </tr>
                    <tr>
                        <td>$seeAllLabel</td>
                        <td>
                            <input type="hidden" name="see_all" value="0">
                            <input type="checkbox" name="see_all" value="1" {$seeAll}/>
                        </td>
                    </tr>
                    <tr>
                        <th colspan="4">$headerSimplified</th>
                    </tr>
                    <tr>
                        <td>$collapseLabel</td>
                        <td>
                            <input type="hidden" name="collapse_menu" value="0">
                            <input type="checkbox" name="collapse_menu" value="1" {$collapseChecked}/>
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
     }
 }