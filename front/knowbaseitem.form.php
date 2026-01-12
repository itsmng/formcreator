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

include ("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('formcreator')) {
   Html::displayNotFoundError();
}

$kb = new KnowbaseItem();

if (isset($_GET["id"])) {
   $kb->check($_GET["id"], READ);

   $active_tab = $_GET['tab'] ?? 'article';

   // Show tabs only when the KB item has attached documents.
   $has_documents = Document_Item::countForItem($kb) > 0;
   if ($has_documents) {
      $_SESSION['plugin_formcreator_page_tabs'] = [
         [
            'label'  => __('Article', 'formcreator'),
            'href'   => FORMCREATOR_ROOTDOC . '/front/knowbaseitem.form.php?id=' . (int) $_GET['id'] . '&tab=article',
            'active' => $active_tab === 'article',
         ],
         [
            'label'  => Document::getTypeName(Session::getPluralNumber()),
            'href'   => FORMCREATOR_ROOTDOC . '/front/knowbaseitem.form.php?id=' . (int) $_GET['id'] . '&tab=documents',
            'active' => $active_tab === 'documents',
         ],
      ];
   } else {
      unset($_SESSION['plugin_formcreator_page_tabs']);
   }

   PluginFormcreatorWizard::header(__('Service catalog', 'formcreator'));

   $available_options = ['item_itemtype', 'item_items_id', 'id'];
   $options           = [];
   foreach ($available_options as $key) {
      if (isset($_GET[$key])) {
         $options[$key] = $_GET[$key];
      }
   }
   $_SESSION['glpilisturl']['KnowbaseItem'] = Plugin::getWebDir('formcreator') . "/front/wizard.php";

   if ($active_tab === 'documents' && $has_documents) {
      echo '<div class="plugin_formcreator_document_wrapper">';
      Document_Item::showForItem($kb);
      echo '</div>';
   } else {
      $kb->showFull($options);
   }

   PluginFormcreatorWizard::footer();

   unset($_SESSION['plugin_formcreator_page_tabs']);
}
