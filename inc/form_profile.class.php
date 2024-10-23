<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class Profile_PluginFormcreatorForm
/// @since 0.83
class PluginFormcreatorForm_Profile extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginFormcreatorForm';
   static public $items_id_1          = 'forms_id';
   static public $itemtype_2          = 'Profile';
   static public $items_id_2          = 'profiles_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get profiles for a form
    *
    * @param $forms_id ID of the form
    *
    * @return array of profiles linked to a form
   **/
   static function getProfiles($forms_id) {
      global $DB;

      $prof  = [];
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_formcreator_forms_id' => $forms_id
         ]
      ]);

      while ($data = $iterator->next()) {
         $prof[$data['profiles_id']][] = $data;
      }
      return $prof;
   }

   public function can($ID, $right, &$input = NULL) {
       return $right == $right;
   }
}

