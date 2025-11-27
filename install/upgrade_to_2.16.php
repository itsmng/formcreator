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
class PluginFormcreatorUpgradeTo2_16 {
   /** @var Migration */
   protected $migration;

   /**
    * @param Migration $migration
    */
   public function upgrade(Migration $migration) {
      global $DB;

      $migration->displayMessage("Upgrade to schema version 2.16");

      $migration->displayMessage("Upgrade glpi_plugin_formcreator_targettickets");

      // add requestsource rule
      if (!$DB->fieldExists('glpi_plugin_formcreator_targettickets', 'requestsource_rule', false)) {
         $migration->addField(
            'glpi_plugin_formcreator_targettickets',
            'requestsource_rule',
            "int(11) NOT NULL DEFAULT '1'",
            ['after' => 'ola_question_ttr']
         );
      }

      // add requestsource question
      if (!$DB->fieldExists('glpi_plugin_formcreator_targettickets', 'requestsource_question', false)) {
         $migration->addField(
            'glpi_plugin_formcreator_targettickets',
            'requestsource_question',
            'integer',
            ['after' => 'requestsource_rule']
         );
      }

      $migration->executeMigration();
   }
}
