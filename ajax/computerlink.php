<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2011 - 2021 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

include ('../../../inc/includes.php');

global $CFG_GLPI;

// Load parameters
$itemtype       = $_REQUEST['itemtype'];

// This should only be used for dropdowns
$isAllowedType = in_array($itemtype, $CFG_GLPI['appliance_types']);
if (!$isAllowedType || !isset($_REQUEST['forms_id']) || !isset($_REQUEST['questions_id']) ) {
   http_response_code(400);
   die;
}

$form = new PluginFormcreatorForm();
$form->getFromDB($_REQUEST['forms_id']);
$fields = $form->getFields();
unset($fields[$_REQUEST['questions_id']]);
$values = [];
foreach ($fields as $id => $field) {
    if ($field->getFieldTypeName() == 'glpiselect') {
        $values[$id] = $field->getLabel();
    }
}

// Build the row content
$rand = mt_rand();
$additions = '<td>';
$additions .= '<label for="dropdown_selectable_tree_root'.$rand.'" id="label_selectable_tree_root">';
$additions .= __('Linked to', 'formcreator');
$additions .= '</label>';
$additions .= '</td>';
$additions .= '<td>';
$additions .= Dropdown::showFromArray('link', $values, [
    'display' => false,
    'display_emptychoice' => true,
    'value' => $_REQUEST['value']
]);
$additions .= '</td>';

echo $additions;
