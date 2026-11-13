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

if (!defined('GLPI_ROOT')) {
   include ('../../../inc/includes.php');
}

Session::checkRight('entity', UPDATE);
if (!(new Plugin())->isActivated('formcreator')) {
   Html::displayNotFoundError();
}

$actor = new PluginFormcreatorTarget_Actor();
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
   Html::displayNotFoundError();
}
$actor->check($id, UPDATE);

Html::popHeader(__('Email followup'), $_SERVER['PHP_SELF']);
if (isset($_POST['update'])) {
   // Only change notification preferences, never the actor or its target.
   if ($actor->update([
      'id' => $id,
      'use_notification' => in_array($_POST['use_notification'] ?? 0, [1, '1'], true) ? 1 : 0,
   ])) {
      echo '<script>window.parent.location.reload();</script>';
   } else {
      Html::back();
   }
} else {
   renderTwigForm([
      'action' => $actor::getFormURL(),
      'buttons' => [
         ['type' => 'submit', 'name' => 'update', 'value' => _x('button', 'Save'), 'class' => 'btn btn-primary'],
      ],
      'content' => [
         __('Email followup') => [
            'visible' => true,
            'inputs' => [
               ['type' => 'hidden', 'name' => 'id', 'value' => $id],
               __('Email followup') => [
                  'type' => 'select',
                  'name' => 'use_notification',
                  'value' => $actor->fields['use_notification'],
                  'values' => [0 => __('No'), 1 => __('Yes')],
               ],
            ],
         ],
      ],
   ]);
}
Html::popFooter();
