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

      if (!$DB->tableExists("glpi_plugin_formcreator_profiles")) {
        $query = "CREATE TABLE `glpi_plugin_formcreator_profiles` (
            `id` int(11) NOT NULL default '0' COMMENT 'RELATION to glpi_profiles (id)',
            `right` char(1) collate utf8_unicode_ci default NULL,
            `entities_id` int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

        $DB->queryOrDie($query, $DB->error());

        include_once(GLPI_ROOT."/plugins/formcreator/inc/profile.class.php");
        PluginFormcreatorProfile::createAdminAccess($_SESSION['glpiactiveprofile']['id']);

        foreach (PluginFormcreatorProfile::getRightsGeneral() as $right) {
            PluginFormcreatorProfile::addDefaultProfileInfos($_SESSION['glpiactiveprofile']['id'],[$right['field'] => $right['default']]);
        }
      }
      if (!$DB->tableExists("glpi_plugin_formcreator_forms_users")) {
        $query = "CREATE TABLE `glpi_plugin_formcreator_forms_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
            `users_id` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`),
            KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());
      }
      if (!$DB->tableExists("glpi_plugin_formcreator_forms_entities")) {
        $query = "CREATE TABLE `glpi_plugin_formcreator_forms_entities` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
            `entities_id` int(11) NOT NULL DEFAULT '0',
            `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`),
            KEY `entities_id` (`entities_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());
      }
      if (!$DB->tableExists("glpi_plugin_formcreator_forms_groups")) {
        $query = "CREATE TABLE `glpi_plugin_formcreator_forms_groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
            `groups_id` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `plugin_formcreator_forms_id` (`plugin_formcreator_forms_id`),
            KEY `groups_id` (`groups_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->queryOrDie($query, $DB->error());
      }
      $migration->dropField('glpi_plugin_formcreator_form_profiles', 'uuid');
      $migration->addField('glpi_plugin_formcreator_forms_profiles', 'entities_id', 'int(11)');
      $migration->addField('glpi_plugin_formcreator_forms_profiles', 'is_recursive', 'tinyint(1)');
      $migration->migrationOneTable('glpi_plugin_formcreator_form_profiles');
   }
}
