<?php

class PluginFormcreatorUpgradeTo2_18
{
    protected Migration $migration;

    /**
     * @param Migration $migration
     */
    public function upgrade(Migration $migration)
    {
        global $DB;

        $query = <<<SQL
        CREATE TABLE IF NOT EXISTS `glpi_plugin_formcreator_forms_groups` (
            `id`                          int(11) NOT NULL AUTO_INCREMENT,
            `plugin_formcreator_forms_id` int(11) NOT NULL,
            `groups_id`                   int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`plugin_formcreator_forms_id`,`groups_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

        $DB->query($query) or plugin_formcreator_upgrade_error($migration);
    }
}
