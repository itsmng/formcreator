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
global $DB;

$itemtype = $_REQUEST['itemtype'] ?? '';
$name = $_REQUEST['name'] ?? '';
$filterItemsId = (int) ($_REQUEST['filter_items_id'] ?? 0);
$filterItemtype = $_REQUEST['filter_itemtype'] ?? '';
$selectedValue = $_REQUEST['value'] ?? '0';

$isAllowedType = in_array($itemtype, $CFG_GLPI['appliance_types'], true);
if (!$isAllowedType || $name === '' || !class_exists($filterItemtype)) {
   http_response_code(400);
   die;
}

$itemTable = $itemtype::getTable();
$filterTable = $filterItemtype === Appliance::class
   ? Appliance_Item::getTable()
   : 'glpi_items_' . strtolower($filterItemtype) . 's';
$filterKey = $filterItemtype::getForeignKeyField();

$values = [0 => Dropdown::EMPTY_VALUE];
if ($filterItemsId > 0 && $DB->tableExists($filterTable)) {
   $iterator = $DB->request([
      'SELECT'    => [
         "$filterTable.items_id",
         "$itemTable.name",
      ],
      'FROM'      => $filterTable,
      'LEFT JOIN' => [
         $itemTable => [
            'ON' => [
               $filterTable => 'items_id',
               $itemTable   => 'id',
            ],
         ],
      ],
      'WHERE'     => [
         "$filterTable.itemtype" => $itemtype,
         "$filterTable.$filterKey" => $filterItemsId,
      ],
      'ORDERBY'   => "$itemTable.name",
   ]);

   foreach ($iterator as $row) {
      $values[$row['items_id']] = $row['name'] ?? '';
   }
}

$rand = mt_rand();
renderTwigTemplate('macros/input.twig', [
   'id'     => $name . '_' . $rand,
   'type'   => 'select',
   'name'   => $name,
   'values' => $values,
   'value'  => $selectedValue,
]);
echo Html::scriptBlock("$(function() {
   pluginFormcreatorInitializeDropdown('$name', '$rand');
});");
