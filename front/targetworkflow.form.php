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

// Check if plugin is activated...
if (!(new Plugin())->isActivated('formcreator')) {
   Html::displayNotFoundError();
}
$targetworkflow = new PluginFormcreatorTargetWorkflow();

// Edit an existing target ticket
if (isset($_POST['update'])) {
   $targetworkflow->update($_POST);
   Html::back();

} else {
   Html::header(
      __('Form Creator', 'formcreator'),
      $_SERVER['PHP_SELF'],
      'admin',
      'PluginFormcreatorForm'
   );

   $itemtype = $targetworkflow::class;
   $targetworkflow->getFromDB((int) $_REQUEST['id']);
   $form = $targetworkflow->getForm();

   $_SESSION['glpilisttitle'][$itemtype] = sprintf(
      __('%1$s = %2$s'),
      $form->getTypeName(1), $form->getName()
   );
   $_SESSION['glpilisturl'][$itemtype]   = $form->getFormURL()."?id=".$form->getID();

   $targetworkflow->showFormHeader($form);

   Html::footer();
}
