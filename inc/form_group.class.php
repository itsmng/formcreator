<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class Group_PluginFormcreatorForm
/// @since 0.83
class PluginFormcreatorForm_Group extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginFormcreatorForm';
   static public $items_id_1          = 'forms_id';
   static public $itemtype_2          = 'Group';
   static public $items_id_2          = 'groups_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get groups for a form
    *
    * @param integer $forms_id ID of the form
    *
    * @return array of groups linked to a form
   **/
   static function getGroups($forms_id) {
      global $DB;

      $groups = [];
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_formcreator_forms_id' => $forms_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $groups[$data['groups_id']][] = $data;
      }
      return $groups;
   }

}

