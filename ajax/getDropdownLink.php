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

global $DB;

// Load parameters
$itemtype       = $_REQUEST['itemtype'];
$name           = $_REQUEST['name'];
$filter_items_id= $_REQUEST['filter_items_id'];
$filter_itemtype = $_REQUEST['filter_itemtype'];

// This should only be used for dropdowns
$tables = [];
$isAllowedType = in_array($itemtype, $CFG_GLPI['appliance_types']);
if (!$isAllowedType) {
   http_response_code(400);
   die;
}

$values = [];

$itemTable = $itemtype::getTable();

$filterTable = 'gpli_items_' . strtolower($filter_itemtype) . 's';
if ($filter_itemtype == Appliance::class) {
    $filterTable =  'glpi_appliances_items';
}
$fk = $filter_itemtype::getForeignKeyField();

$query = <<<SQL
    SELECT $filterTable.items_id, $itemTable.name
    FROM $filterTable
    LEFT JOIN $itemTable ON $filterTable.items_id = $itemTable.id
    WHERE $filterTable.itemtype = '$itemtype' 
    AND $filterTable.$fk = $filter_items_id;
SQL;
$results = $DB->query($query);
while ($row = $DB->fetchAssoc($results)) {
   $values[$row['items_id']] = $row['name'] ?? '';
}

Dropdown::showFromArray($name, $values, ['display_emptychoice' => true]);
