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
 */

include ('../../../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_POST['type']) || empty($_POST['type'])) {
   return;
}

$display = false;
$rand    = mt_rand();
$prefix  = '';
$suffix  = '';

if (isset($_POST['prefix']) && !empty($_POST['prefix'])) {
   $prefix = $_POST['prefix'] . '[';
   $suffix = ']';
} else {
   $_POST['prefix'] = '';
}

echo "<table class='tab_format'><tr>";
switch ($_POST['type']) {
   case 'User':
      echo "<td>";
      User::dropdown([
         'name' => $prefix . 'users_id' . $suffix,
      ]);
      echo "</td>";
      $display = true;
      break;

   case 'Group':
      echo "<td>";
      $params             = [
         'rand' => $rand,
         'name' => $prefix . 'groups_id' . $suffix,
      ];
      $params['toupdate'] = [
         'value_fieldname' => 'value',
         'to_update'       => "subvisibility$rand",
         'url'             => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
         'moreparams'      => [
            'items_id' => '__VALUE__',
            'type'     => $_POST['type'],
            'prefix'   => $_POST['prefix'],
         ],
      ];

      Group::dropdown($params);
      echo "</td><td>";
      echo "<span id='subvisibility$rand'></span>";
      echo "</td>";
      $display = true;
      break;

   case 'Entity':
      echo "<td>";
      Entity::dropdown([
         'entity' => $_SESSION['glpiactiveentities'],
         'value'  => $_SESSION['glpiactive_entity'],
         'name'   => $prefix . 'entities_id' . $suffix,
      ]);
      echo "</td><td>";
      echo __('Child entities');
      echo "</td><td>";
      Dropdown::showYesNo($prefix . 'is_recursive' . $suffix);
      echo "</td>";
      $display = true;
      break;

   case 'Profile':
      echo "<td>";
      $params             = [
         'rand' => $rand,
         'name' => $prefix . 'profiles_id' . $suffix,
      ];
      $params['toupdate'] = [
         'value_fieldname' => 'value',
         'to_update'       => "subvisibility$rand",
         'url'             => $CFG_GLPI["root_doc"] . "/ajax/subvisibility.php",
         'moreparams'      => [
            'items_id' => '__VALUE__',
            'type'     => $_POST['type'],
            'prefix'   => $_POST['prefix'],
         ],
      ];

      Profile::dropdown($params);
      echo "</td><td>";
      echo "<span id='subvisibility$rand'></span>";
      echo "</td>";
      $display = true;
      break;
}

if ($display && (!isset($_POST['nobutton']) || !$_POST['nobutton'])) {
   echo "<td><input type='submit' name='addvisibility' value=\"" . _sx('button', 'Add') . "\"
                class='submit'></td>";
} else {
   echo "<td>&nbsp;</td>";
}
echo "</tr></table>";
