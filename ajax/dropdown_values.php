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

Session::checkRight('entity', UPDATE);

if (!isset($_REQUEST['dropdown_itemtype'])
    || $_REQUEST['dropdown_itemtype'] == '0'
    || !class_exists($_REQUEST['dropdown_itemtype'])) {
   Dropdown::showFromArray(
      'dropdown_default_value',
      [], [
         'display_emptychoice'   => true
      ]
   );
} else {
   $itemtype = $_REQUEST['dropdown_itemtype'];
   $question = new PluginFormcreatorQuestion();
   $question->getFromDB((int) $_REQUEST['id']);
   $defaultValue = isset($question->fields['default_values'])
                   ? $question->fields['default_values']
                   : 0;

   $fieldType = $question->fields['fieldtype'] ?? 'dropdown';
   $field = PluginFormcreatorFields::getFieldInstance($fieldType, $question);
   $searchParams = $field->buildParams();
   $searchParams['itemtype'] = $itemtype;
   if (array_key_exists('entity', $searchParams)) {
      $searchParams['entity_restrict'] = $searchParams['entity'];
      unset($searchParams['entity']);
   }
   $values = Dropdown::getDropdownValue($searchParams, false);
   $selectOptions = [];

   foreach ($values['results'] as $item) {
      if (!isset($item['text'])) {
         continue;
      }

      if (isset($item['children']) && is_array($item['children'])) {
         $optgroup = [];
         foreach ($item['children'] as $child) {
            if (isset($child['id']) && isset($child['text'])) {
               $optgroup[$child['id']] = $child['text'];
            }
         }
         $selectOptions[$item['text']] = $optgroup;
      } elseif (isset($item['id'])) {
         $selectOptions[$item['id']] = $item['text'];
      }
   }

   $options = [
      'name'  => 'dropdown_default_value',
      'rand'  => mt_rand(),
      'value' => $defaultValue,
   ];
   renderTwigTemplate('macros/wrappedInput.twig', [
      'title' => $itemtype::getTypeName(),
      'input' => [
         'type' => 'select',
         'name' => $options['name'],
         'value' => $options['value'],
         'values' => $selectOptions,
         'col_lg' => 12,
         'col_md' => 12,
      ]
   ]);
}
