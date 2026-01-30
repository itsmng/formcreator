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

// Check required parameters
if (ctype_digit($_REQUEST['itemtype']) || !isset($_REQUEST['itemtype']) || !isset($_REQUEST['root']) || !isset($_REQUEST['maxDepth'])) {
   http_response_code(400);
   die;
}

// Load parameters
$itemtype       = $_REQUEST['itemtype'];
$root           = $_REQUEST['root'];
$depth          = $_REQUEST['maxDepth'];
$selectableRoot = $_REQUEST['selectableRoot'];

// This should only be used for dropdowns
if (!is_a($itemtype, CommonTreeDropdown::class, true)) {
   http_response_code(400);
   die;
}

// Build the row content
$inputs = [
   __('Subtree root', 'formcreator') => [
      'type' => 'select',
      'name' => 'show_tree_root',
      'value' => $root,
      'values' => getOptionForItems($itemtype),
      'col_lg' => 12,
      'col_md' => 12,
   ],
   __('Selectable', 'formcreator') => [
      'type' => 'checkbox',
      'name' => 'selectable_tree_root',
      'value' => $selectableRoot,
      'col_lg' => 12,
      'col_md' => 12,
   ],
   __('Limit subtree depth', 'formcreator') => [
      'type' => 'number',
      'name' => 'show_tree_depth',
      'style' => 'width: 100%;',
      'value' => $depth,
      'min' => 0,
      'max' => 16,
      'after' => '0 -> ' . __('No limit'),
      'col_lg' => 12,
      'col_md' => 12,
   ],
];

ob_start();
foreach($inputs as $title => $input) {
   renderTwigTemplate('macros/wrappedInput.twig', [
      'title' => $title,
      'input' => $input,
   ]);
}
$additions = ob_get_clean();
echo $additions;
