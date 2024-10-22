<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class Entity_PluginFormcreatorForm
/// @since 0.83
class PluginFormcreatorForm_Entity extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'PluginFormcreatorForm';
   static public $items_id_1          = 'forms_id';
   static public $itemtype_2          = 'Entity';
   static public $items_id_2          = 'entities_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;


   /**
    * Get entities for a form
    *
    * @param PluginFormcreatorForm $form PluginFormcreatorForm instance
    *
    * @return array of entities linked to a form
   **/
   static function getEntities($form) {
      global $DB;

      $ent   = [];
      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_formcreator_forms_id' => $form->fields['id']
         ]
      ]);

      while ($data = $iterator->next()) {
         $ent[$data['entities_id']][] = $data;
      }
      return $ent;
   }

}

