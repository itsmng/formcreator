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

$itemtype = $_REQUEST['itemtype'] ?? '';
$formId = (int) ($_REQUEST['forms_id'] ?? 0);
$questionId = (int) ($_REQUEST['questions_id'] ?? 0);
$selectedLink = (int) ($_REQUEST['value'] ?? 0);

$isAllowedType = in_array($itemtype, $CFG_GLPI['appliance_types'], true);
if (!$isAllowedType || $formId <= 0 || $questionId <= 0) {
   http_response_code(400);
   die;
}

$form = new PluginFormcreatorForm();
if (!$form->getFromDB($formId)) {
   http_response_code(404);
   die;
}

$fields = $form->getFields();
unset($fields[$questionId]);

$values = [0 => Dropdown::EMPTY_VALUE];
foreach ($fields as $id => $field) {
   if ($field->getFieldTypeName() !== 'glpiselect') {
      continue;
   }

   $values[$id] = $field->getLabel();
}

renderTwigTemplate('macros/wrappedInput.twig', [
   'title' => __('Linked to', 'formcreator'),
   'input' => [
      'type'   => 'select',
      'name'   => 'link',
      'values' => $values,
      'value'  => $selectedLink,
      'col_lg' => 12,
      'col_md' => 12,
   ],
]);
