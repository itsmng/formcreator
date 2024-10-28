<?php


class PluginFormcreatorProfile extends CommonDBTM {

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         return __('Formcreator', 'formcreator');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
       if ($item->getType() == 'Profile') {
          $ID   = $item->getID();
          $prof = new self();

          $right = self::getRightsGeneral();
          $rights = [];
          foreach ($right as $r) {
              $rights[$r['field']] = $r['default'];
          }
          $prof->showForm($ID);
       }
       return true;
   }
      
   static function canCreate() {
      if (isset($_SESSION['profile'])) {
        return ($_SESSION['profile']['formcreator'] == 'w');
      }
      return false;
   }

   static function canView() {
      if (isset($_SESSION['profile'])) {
        return ($_SESSION['profile']['formcreator'] == 'w'
                || $_SESSION['profile']['formcreator'] == 'r');
      }
      return false;
   }

   static function createAdminAccess($ID) {
      $myProf = new self();
      // Only create profile if it's new
      if (!$myProf->getFromDB($ID)) {
      // Add entry to permissions database giving the user write privileges
         $myProf->add(array('id'    => $ID,
                            'right' => 'w'));
      }
   }

   static function addDefaultProfileInfos($profiles_id, $rights) {
      $profileRight = new ProfileRight();
      foreach ($rights as $right => $value) {
         if (!countElementsInTable('glpi_profilerights',
                                   ['profiles_id' => $profiles_id, 'name' => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);
            //Add right to the current session
            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }

      /**
    * Initialize profiles
    */
   static function initProfile() {
      global $DB;
      $profile = new self();
      $dbu = new DbUtils();
      //Add new rights in glpi_profilerights table
      foreach ($profile->getRightsGeneral() as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                  ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                              AND `name` LIKE '%pluginformcreator%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }
   }

   static function getRightsGeneral() {
      $rights = [
          [
              'itemtype'  => 'PluginFormcreatorForm',
              'label'     => __('Access formcreator\'s forms', 'formcreator'),
              'field'     => 'pluginformcreatorform_public',
              'rights'    =>  [READ    => __('Read')],
              'default'   => READ
          ],
          [
              'itemtype'  => 'PluginFormcreatorForm',
              'label'     => __('Profile infos', 'formcreator'),
              'field'     => 'pluginformcreatorform_profileinfos',
              'rights'    =>  [READ    => __('Read')],
              'default'   => READ
          ],
          [
              'itemtype'  => 'PluginFormcreatorForm',
              'label'     => __('Saved search', 'formcreator'),
              'field'     => 'pluginformcreatorform_savedsearch',
              'rights'    =>  [READ    => __('Read')],
              'default'   => READ
          ],
          [
              'itemtype'  => 'PluginFormcreatorForm',
              'label'     => __('Saved search', 'formcreator'),
              'field'     => 'pluginformcreatorform_savedsearch',
              'rights'    =>  [READ    => __('Read')],
              'default'   => READ
          ],
          [
              'itemtype'  => 'PluginFormcreatorForm',
              'label'     => __('Ticket status counter', 'formcreator'),
              'field'     => 'pluginformcreatorform_ticketcounters',
              'rights'    =>  [READ    => __('Read')],
              'default'   => READ
          ],
      ];
      return $rights;
   }

   function showForm($profiles_id = 0, $openform = true, $closeform = true) {
      if (!Session::haveRight("profile",READ)) {
         return false;
      }
      
      echo "<div class='firstbloc'>";
      if (($canedit = Session::haveRight('profile', UPDATE))
          && $openform) {
         $profile = new Profile();
        
         echo "<form method='post' action='".$profile->getFormURL()."'>";
      }
    
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      $rights = $this->getRightsGeneral();
      $profile->displayRightsChoiceMatrix($rights, ['default_class' => 'tab_bg_2',
                                                         'title'         => __('General')]);

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}
