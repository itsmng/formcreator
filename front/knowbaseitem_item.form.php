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

use Glpi\Event;

include ("../../../inc/includes.php");

// Check if plugin is activated...
if (!(new Plugin())->isActivated('formcreator')) {
   Html::displayNotFoundError();
}

Session::checkLoginUser();

$item = new KnowbaseItem_Item();

if (isset($_POST["add"])) {
    if (!isset($_POST['knowbaseitems_id']) || !isset($_POST['items_id']) || !isset($_POST['itemtype'])) {
        $message = __('Mandatory fields are not filled!');
        Session::addMessageAfterRedirect($message, false, ERROR);
        
        Html::redirect($_SERVER['HTTP_REFERER']);
    }

    if ($item->add($_POST)) {
        Event::log(
            $_POST["knowbaseitems_id"],
            "knowbaseitem",
            4,
            "tracking",
            sprintf(__('%s adds a link with an knowledge base'), $_SESSION["glpiname"])
        );
        
        $message = __('Link successfully added');
        Session::addMessageAfterRedirect($message, false, INFO);
    } else {
        $message = __('Failed to add link');
        Session::addMessageAfterRedirect($message, false, ERROR);
    }
    
    Html::redirect($_SERVER['HTTP_REFERER']);
}

PluginFormcreatorWizard::header(__('Knowledge base link', 'formcreator'));

echo "<div class='center'>";
echo "<p>" . __('Processing request...') . "</p>";
echo "</div>";

$redirect_url = $_SERVER['HTTP_REFERER'] ?? Plugin::getWebDir('formcreator') . "/front/wizard.php";
Html::redirect($redirect_url);

PluginFormcreatorWizard::footer();