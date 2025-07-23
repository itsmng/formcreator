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

      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_configs` (
          `id`                          int(11) NOT NULL AUTO_INCREMENT,
          `name`                        varchar(255) NOT NULL DEFAULT '',
          `plugin_formcreator_forms_id` int(11) NOT NULL DEFAULT '0',
          `target_name`                 varchar(255) NOT NULL DEFAULT '',
          `type_rule`                   int(11) NOT NULL DEFAULT '0',
          `type_question`               int(11) NOT NULL DEFAULT '0',
          `content`                     longtext,
          `uuid`                        varchar(255) DEFAULT NULL,
         PRIMARY KEY (`id`),
         UNIQUE KEY `name` (`name`)
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $DB->query($query) or plugin_formcreator_upgrade_error($migration);
   }
}
