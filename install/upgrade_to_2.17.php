<?php

class PluginFormcreatorUpgradeTo2_17
{
    protected Migration $migration;

    /**
     * @param Migration $migration
     */
    public function upgrade(Migration $migration)
    {
        global $DB;

        $query = "ALTER TABLE `glpi_plugin_formcreator_targetworkflows` ADD COLUMN `workflows_id` int(11) DEFAULT NULL";

        $DB->query($query) or plugin_formcreator_upgrade_error($migration);
    }
}