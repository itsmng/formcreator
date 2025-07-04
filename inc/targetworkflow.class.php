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

use GlpiPlugin\Formcreator\Exception\ImportFailureException;
use GlpiPlugin\Formcreator\Exception\ExportFailureException;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFormcreatorTargetWorkflow extends PluginFormcreatorAbstractTarget
{
   const ASSOCIATE_RULE_NONE = 1;
   const ASSOCIATE_RULE_SPECIFIC = 2;
   const ASSOCIATE_RULE_ANSWER = 3;
   const ASSOCIATE_RULE_LAST_ANSWER = 4;

   const REQUESTTYPE_NONE = 0;
   const REQUESTTYPE_SPECIFIC = 1;
   const REQUESTTYPE_ANSWER = 2;

   protected function getItem_User() {
      return $this;
   }

   protected function getItem_Group() {
      return $this;
   }

   protected function getItem_Supplier() {
      return $this;
   }

   protected function getItem_Item() {
      return $this;
   }

   protected function getTemplateItemtypeName(): string {
      return 'workflow';
   }

   protected function getTemplatePredefinedFieldItemtype(): string {
      return 'workflow';
   }

   protected function getCategoryFilter()
   {
      return array('workflow');
   }

   public static function getTypeName($nb = 1) {
      return _n('Target workflow', 'Target workflow', $nb, 'formcreator');
   }

   protected function getTargetItemtypeName(): string {
      $plugin = new Plugin();
      if ($plugin->isActivated('workflows')) {
          return PluginWorkflowsWorkflow::class;
      }
   }

   public static function getEnumAssociateRule() {
      return [
         self::ASSOCIATE_RULE_NONE        => __('None', 'formcreator'),
         self::ASSOCIATE_RULE_SPECIFIC    => __('Specific asset', 'formcreator'),
         self::ASSOCIATE_RULE_ANSWER      => __('Equals to the answer to the question', 'formcreator'),
         self::ASSOCIATE_RULE_LAST_ANSWER => __('Last valid answer', 'formcreator'),
      ];
   }

   public static function getEnumRequestTypeRule() {
      return [
         self::REQUESTTYPE_NONE      => __('Default or from a template', 'formcreator'),
         self::REQUESTTYPE_SPECIFIC  => __('Specific type', 'formcreator'),
         self::REQUESTTYPE_ANSWER    => __('Equals to the answer to the question', 'formcreator'),
      ];
   }

   public function rawSearchOptions() {
      $tab[] = [
         'id'                 => '2',
         'table'              => $this::getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'searchtype'         => 'contains',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this::getTable(),
         'field'              => 'target_name',
         'name'               => __('Ticket title', 'formcreator'),
         'datatype'           => 'string',
         'searchtype'         => 'contains',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this::getTable(),
         'field'              => 'content',
         'name'               => __('Content', 'formcreator'),
         'datatype'           => 'text',
         'searchtype'         => 'contains',
         'massiveaction'      => false
      ];

      return $tab;
   }

   /**
    * Show the Form for the adminsitrator to edit in the config page
    *
    * @param  array  $options Optional options
    * @return void
    */
   public function showForm($ID, $options = []) {
      if ($ID == 0) {
         // Not used for now
         $title =  __('Add a target ', 'formcreator');
      } else {
         $title =  __('Edit a target', 'formcreator');
      }

      $form = [
         'action' => self::getFormURL(),
         'buttons' => [
            [
               'name' => 'update',
               'type' => 'submit',
               'value' => _x('button', 'Save'),
               'class' => 'btn btn-secondary'
            ]
         ],
         'content' => [
             $title => [
                 'visible' => true,
                 'inputs' => [
                     [
                         'type' => 'hidden',
                         'name' => 'id',
                         'value' => $this->fields['id'],
                     ],
                     __('Workflow', 'workflows') => [
                         'type' => 'select',
                         'name' => 'workflows_id',
                         'values' => getOptionForItems(PluginWorkflowsWorkflow::class),
                         'value' => $this->fields['workflows_id'],
                         'required' => true,
                         'col_lg' => 12,
                         'col_md' => 12,
                     ],
                     __('Description', 'formcreator') => [
                         'type' => 'textarea',
                         'name' => 'content',
                         'value' => $this->fields['content'],
                         'col_lg' => 12,
                         'col_md' => 12,
                     ]
                 ]
             ],
             __('Condition to create the target', 'formcreator') => $this->showConditionsSettings(),
         ]
      ];
      renderTwigForm($form);
   }

   /**
    * Show settings to handle composite tickets
    * @param string $rand
    */
   protected function showCompositeTicketSettings($rand = '') {
      global $DB;

      $question = new PluginFormcreatorQuestion();
      $elements = [
         PluginFormcreatorTargetTicket::class => __('An other destination of this form', 'formcreator'),
         Ticket::class                        => __('An existing ticket', 'formcreator'),
         PluginFormcreatorQuestion::class     => __('A ticket from an answer to a question'),
      ];

      $ticketList = '';
      $rows = $DB->request([
         'FROM'   => PluginFormcreatorItem_TargetTicket::getTable(),
         'WHERE'  => [
            'plugin_formcreator_targettickets_id' => $this->getID()
         ]
      ]);
      foreach ($rows as $row) {
         $icons = '&nbsp;'.Html::getSimpleForm(
            PluginFormcreatorItem_TargetTicket::getFormURL(),
            'purge',
            _x('button', 'Delete permanently'),
            ['id' => $row['id']],
            'fa-times-circle'
         );
         $itemtype = $row['itemtype'];
         $item = new $itemtype();
         $item->getFromDB($row['items_id']);
         switch ($itemtype) {
            case Ticket::getType():
               $ticketList .= Ticket_Ticket::getLinkName($row['link']) . ' '
                  . $itemtype::getTypeName() . ' '
                  . '<span style="font-weight:bold">' . $item->getField('name') . '</span> '
                  . $icons . '<br>';
               break;

            case PluginFormcreatorTargetTicket::getType():
               $ticketList .= Ticket_Ticket::getLinkName($row['link']) . ' '
                  . $itemtype::getTypeName() . ' '
                  . '<span style="font-weight:bold">' . $item->getField('name') . '</span> '
                  . $icons . '<br>';
               break;

            case PluginFormcreatorQuestion::getType():
               $ticketList .= Ticket_Ticket::getLinkName($row['link']) . ' '
                  . $itemtype::getTypeName() . ' '
                  . '<span style="font-weight:bold">' . $item->getField('name') . '</span> '
                  . $icons . '<br>';
               break;
         }
      }

      $block = [
         'visible' => true,
         'inputs' => [
            __('Link type') => [
               'type' => 'select',
               'type' => 'select',
               'name' => '_linktype',
               'values' => [
                  Ticket_Ticket::LINK_TO     => __('Linked to', 'formcreator'),
                  Ticket_Ticket::PARENT_OF   => __('Parent of', 'formcreator'),
                  Ticket_Ticket::SON_OF    => __('Child of', 'formcreator'),
                  Ticket_Ticket::DUPLICATE_WITH => __('Duplicate of', 'formcreator'),
               ],
               'value' => Ticket_Ticket::LINK_TO,
               'col_lg' => 6,
            ],
            __('Link item type') => [
               'type' => 'select',
               'id' => 'selectForLinkItemtypeTargetTicket',
               'name' => '_link_itemtype',
               'values' => $elements,
               'col_lg' => 6,
               'hooks' => [
                  'change' => <<<JS
                  const val = this.value;
                  $('#selectForLinkTargetTicket').attr('disabled', val != 'PluginFormcreatorTargetTicket');
                  $('#selectForLinkTicket').attr('disabled', val != 'Ticket');
                  $('#selectForLinkQuestion').attr('disabled', val != 'PluginFormcreatorQuestion');
                  JS,
               ]

            ],
            __('Target Ticket') => [
               'type' => 'select',
               'id' => 'selectForLinkTargetTicket',
               'name' => '_link_targettickets_id',
               'values' => getOptionForItems(PluginFormcreatorTargetTicket::class, [], true, false, ['id' => $this->getID()]),
            ],
            __('Ticket') => [
               'type' => 'select',
               'id' => 'selectForLinkTicket',
               'name' => '_link_tickets_id',
               'values' => getOptionForItems(Ticket::class),
               'disabled' => ''
            ],
            __('Question') => [
               'type' => 'select',
               'id' => 'selectForLinkQuestion',
               'name' => '_link_plugin_formcreator_questions_id',
               'values' => $question->getQuestionsFromFormBySection(
                  $this->getForm()->getID(),
                  ['fieldtype' => ['glpiselect']],
                  $this->fields['destination_entity_value']
               ),
               'disabled' => ''
            ],
            '' => [
               'content' => $ticketList,
            ]
         ],
      ];
      return $block;
      // show already linked items

      echo '</td>';
      echo '</tr>';
   }

   protected function getTargetTemplate(array $data): int {
      global $DB;

      $targetItemtype = $this->getTemplateItemtypeName();
      $targetTemplateFk = $targetItemtype::getForeignKeyField();
      if ($targetItemtype::isNewID($this->fields[$targetTemplateFk]) && !ITILCategory::isNewID($data['itilcategories_id'])) {
         $rows = $DB->request([
            'SELECT' => ["{$targetTemplateFk}_incident", "{$targetTemplateFk}_demand"],
            'FROM'   => ITILCategory::getTable(),
            'WHERE'  => ['id' => $data['itilcategories_id']]
         ]);
         if ($row = $rows->next()) { // assign ticket template according to resulting ticket category and ticket type
            return ($data['type'] == Ticket::INCIDENT_TYPE
                    ? $row["{$targetTemplateFk}_incident"]
                    : $row["{$targetTemplateFk}_demand"]);
         }
      }

      return $this->fields['tickettemplates_id'] ?? 0;
   }

   public function getDefaultData(PluginFormcreatorFormAnswer $formanswer): array {
      return [];
   }

   /**
    * Save form data to the target
    *
    * @param  PluginFormcreatorFormAnswer $formanswer    Answers previously saved
    *
    * @return Ticket|null Generated ticket if success, null otherwise
    */
   public function save(PluginFormcreatorFormAnswer $formanswer) {
      $plugin = new Plugin();
      if ($plugin->isActivated('workflows')) {
          $workflow = new PluginWorkflowsWorkflow();
          $workflow->getFromDB($this->fields['workflows_id']);
          $workflow->run($formanswer->getAnswers($formanswer->fields['id']));
      }
      return $formanswer;
   }

   protected function setTargetLocation($data, $formanswer) {
      global $DB;

      $location = null;
      switch ($this->fields['location_rule']) {
         case self::LOCATION_RULE_ANSWER:
            $location = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->fields['id'],
                  'plugin_formcreator_questions_id'   => $this->fields['location_question']
               ]
            ])->next();
            if (ctype_digit($location['answer'])) {
               $location = $location['answer'];
            }
            break;
         case self::LOCATION_RULE_SPECIFIC:
            $location = $this->fields['location_question'];
            break;
      }
      if (!is_null($location)) {
         $data['locations_id'] = $location;
      }

      return $data;
   }

   protected function setTargetType(array $data, PluginFormcreatorFormAnswer $formanswer) {
      global $DB;

      $type = null;
      switch ($this->fields['type_rule']) {
         case self::REQUESTTYPE_ANSWER:
            $type = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->getID(),
                  'plugin_formcreator_questions_id'   => $this->fields['type_question']
               ]
            ])->next();
            $type = $type['answer'];
            break;
         case self::REQUESTTYPE_SPECIFIC:
            $type = $this->fields['type_question'];
            break;
         default:
            $type = null;
      }
      if (!is_null($type)) {
         $data['type'] = $type;
      }

      return $data;
   }

   protected  function showTypeSettings($rand = '') {
      $question = new PluginFormcreatorQuestion();
      $block = [
         'visible' => true,
         'inputs' => [
            __('Request type') => [
               'type' => 'select',
               'id' => 'selectForTypeRuleTargetTicket',
               'name' => 'type_rule',
               'values' => static::getEnumRequestTypeRule(),
               'value' => $this->fields['type_rule'],
               'hooks' => [
                  'change' => <<<JS
                     const val = this.value;
                     $('#selectForTypeSpecificTargetTicket').attr('disabled', val != 1);
                     $('#selectForTypeQuestionTargetTicket').attr('disabled', val != 2);
                  JS,
               ]
            ],
            __('Type', 'formcreator') => [
               'type' => 'select',
               'id' => 'selectForTypeSpecificTargetTicket',
               'name' => '_type_specific',
               'values' => Ticket::getTypes(),
               'value' => $this->fields['type_question'],
               $this->fields['type_rule'] == self::REQUESTTYPE_SPECIFIC ? '' : 'disabled' => true,
            ],
            __('Question', 'formcreator') => [
               'type' => 'select',
               'id' => 'selectForTypeQuestionTargetTicket',
               'name' => '_type_question',
               'values' => $question->getQuestionsFromFormBySection(
                  $this->getForm()->getID(),
                  [
                     'fieldtype' => ['requesttype'],
                  ],
               ),
               $this->fields['type_rule'] == self::REQUESTTYPE_ANSWER ? '' : 'disabled' => true,
            ]
         ],
      ];
      return $block;
   }

   protected function showAssociateSettings($rand = '') {
      $options = json_decode($this->fields['associate_question'], true);
      if (!is_array($options)) {
         $options = [];
      }
      $options['_canupdate'] = true;
      $itemTargetTicket = new PluginFormcreatorItem_TargetTicket();
      $rows = $itemTargetTicket->find([
         self::getForeignKeyField() => $this->getID(),
         [
            'NOT' => ['itemtype' => [PluginFormcreatorTargetTicket::class, Ticket::class]],
         ],
      ]);
      foreach ($rows as $row) {
         $options['items_id'][$row['itemtype']][$row['id']] = $row['items_id'];
      }
      ob_start();
      Item_Ticket::itemAddForm(new Ticket(), $options);
      $content = ob_get_clean();
      $question = new PluginFormcreatorQuestion();
      $block = [
         'visible' => true,
         'inputs' => [
            __('Associated elements') => [
               'type' => 'select',
               'name' => 'associate_rule',
               'values' => static::getEnumAssociateRule(),
               'value' => $this->fields['associate_rule'],
            ],
            __('Question', 'formcreator') => [
               'type' => 'select',
               'id' => 'selectForAssociateQuestionTargetTicket',
               'name' => 'associate_question',
               'values' => $question->getQuestionsFromFormBySection(
                  $this->getForm()->getID(),
                  [ 'fieldtype' => 'glpiselect', ],
               ),
            ],
            __('Item ', 'formcreator') => [
               'content' => $content,
            ]
         ]
      ];
      return $block;
   }

   /**
    * @param array $data data of the target
    * @param PluginFormcreatorFormAnswer $formanswer Answers to the form used to populate the target
    * @return array
    */
   protected function setTargetAssociatedItem(array $data, PluginFormcreatorFormAnswer $formanswer) : array {
      global $DB, $CFG_GLPI;

      switch ($this->fields['associate_rule']) {
         case self::ASSOCIATE_RULE_ANSWER:
            // find the itemtype of the associated item
            $associateQuestion = $this->fields['associate_question'];
            $question = new PluginFormcreatorQuestion();
            $question->getFromDB($associateQuestion);
            /** @var  GlpiPlugin\Formcreator\Field\DropdownField */
            $field = $question->getSubField();
            $itemtype = $field->getSubItemtype();

            // find the id of the associated item
            $item = $DB->request([
               'SELECT' => ['answer'],
               'FROM'   => PluginFormcreatorAnswer::getTable(),
               'WHERE'  => [
                  'plugin_formcreator_formanswers_id' => $formanswer->fields['id'],
                  'plugin_formcreator_questions_id'   => $associateQuestion
               ]
            ])->next();
            $itemId = $item['answer'];

            // associate the item if it exists
            if (!class_exists($itemtype)) {
               return $data;
            }
            $item = new $itemtype();
            if ($item->getFromDB($itemId)) {
               $data['items_id'] = [$itemtype => [$itemId => $itemId]];
            }
            break;

         case self::ASSOCIATE_RULE_SPECIFIC:
            $itemTargetTicket = new PluginFormcreatorItem_TargetTicket();
            $rows = $itemTargetTicket->find([
               self::getForeignKeyField() => $this->getID(),
               [
                  'NOT' => ['itemtype' => [PluginFormcreatorTargetTicket::class, Ticket::class]],
               ],
            ]);
            $data['items_id'] = [];
            foreach ($rows as $row) {
               $data['items_id'][$row['itemtype']] [$row['items_id']] = $row['items_id'];
            }
            break;

         case self::ASSOCIATE_RULE_LAST_ANSWER:
            $form_id = $formanswer->fields['id'];

            // Get all answers for glpiselect questions of this form, ordered
            // from last to first displayed
            $answers = $DB->request([
               'SELECT' => ['answer.plugin_formcreator_questions_id', 'answer.answer', 'question.values'],
               'FROM' => PluginFormcreatorAnswer::getTable() . ' AS answer',
               'JOIN' => [
                  PluginFormcreatorQuestion::getTable() . ' AS question' => [
                     'ON' => [
                        'answer' => 'plugin_formcreator_questions_id',
                        'question' => 'id',
                     ]
                  ]
               ],
               'WHERE' => [
                  'answer.plugin_formcreator_formanswers_id' => $form_id,
                  'question.fieldtype'                       => "glpiselect",
               ],
               'ORDER' => [
                  'row DESC',
                  'col DESC',
               ]
            ]);

            foreach ($answers as $answer) {
               // Skip if the object type is not valid asset type
               $question = new PluginFormcreatorQuestion();
               $question->getFromDB($answer[PluginFormcreatorQuestion::getForeignKeyField()]);
               /** @var  GlpiPlugin\Formcreator\Field\DropdownField */
               $field = $question->getSubField();
               $field->deserializeValue($answer['answer']);
               $itemtype = $field->getSubItemtype();
               if (!in_array($itemtype, $CFG_GLPI['ticket_types'])) {
                  continue;
               }

               // Skip if question was not answered
               if (empty($answer['answer'])) {
                  continue;
               }

               // Skip if question is not visible
               if (!$formanswer->isFieldVisible($answer['plugin_formcreator_questions_id'])) {
                  continue;
               }

               // Skip if item doesn't exist in the DB (shouldn't happen)
               $item = new $itemtype();
               if (!$item->getFromDB($answer['answer'])) {
                  continue;
               }

               // Found a valid answer, stop here
               $data['items_id'] = [
                  $itemtype => [$answer['answer'] => $answer['answer']]
               ];
               break;
            }

            break;
      }

      return $data;
   }

   public static function import(PluginFormcreatorLinker $linker, array $input = [], int $containerId = 0) {
      global $DB;

      if (!isset($input['uuid']) && !isset($input['id'])) {
         throw new ImportFailureException(sprintf('UUID or ID is mandatory for %1$s', static::getTypeName(1)));
      }

      $formFk = PluginFormcreatorForm::getForeignKeyField();
      $input[$formFk] = $containerId;
      $input['_skip_checks'] = true;
      $input['_skip_create_actors'] = true;

      $item = new self;
      // Find an existing target to update, only if an UUID is available
      $itemId = false;
      /** @var string $idKey key to use as ID (id or uuid) */
      $idKey = 'id';
      if (isset($input['uuid'])) {
         $idKey = 'uuid';
         $itemId = plugin_formcreator_getFromDBByField(
            $item,
            'uuid',
            $input['uuid']
         );
      }

      // Escape text fields
      foreach (['name'] as $key) {
         $input[$key] = $DB->escape($input[$key]);
      }

      // Add or update
      $originalId = $input[$idKey];
      if ($itemId !== false) {
         $input['id'] = $itemId;
         $item->update($input);
      } else {
         unset($input['id']);
         $itemId = $item->add($input);
      }
      if ($itemId === false) {
         $typeName = strtolower(self::getTypeName());
         throw new ImportFailureException(sprintf(__('Failed to add or update the %1$s %2$s', 'formceator'), $typeName, $input['name']));
      }

      // add the target to the linker
      $linker->addObject($originalId, $item);

      $subItems = [
         '_actors'            => PluginFormcreatorTarget_Actor::class,
         '_ticket_relations'  => PluginFormcreatorItem_TargetTicket::class,
         '_conditions'        => PluginFormcreatorCondition::class,
      ];
      $item->importChildrenObjects($item, $linker, $subItems, $input);

      return $itemId;
   }

   public static function countItemsToImport(array $input) : int {
      $subItems = [
         '_actors'            => PluginFormcreatorTarget_Actor::class,
         '_ticket_relations'  => PluginFormcreatorItem_TargetTicket::class,
         '_conditions'        => PluginFormcreatorCondition::class,
      ];

      return 1 + self::countChildren($subItems, $input);
   }

   protected function getTaggableFields() {
      return [
         'target_name',
         'content',
      ];
   }

   /**
    * Export in an array all the data of the current instanciated targetticket
    * @return array the array with all data (with sub tables)
    */
   public function export(bool $remove_uuid = false) : array {
      if ($this->isNewItem()) {
         throw new ExportFailureException(sprintf(__('Cannot export an empty object: %s', 'formcreator'), $this->getTypeName()));
      }

      $export = $this->fields;

      // remove key and fk
      $formFk = PluginFormcreatorForm::getForeignKeyField();
      unset($export[$formFk]);

      $subItems = [
         '_actors'            => PluginFormcreatorTarget_Actor::class,
         '_ticket_relations'  => PluginFormcreatorItem_TargetTicket::class,
         '_conditions'        => PluginFormcreatorCondition::class,
      ];
      $export = $this->exportChildrenObjects($subItems, $export, $remove_uuid);

      // remove ID or UUID
      $idToRemove = 'id';
      if ($remove_uuid) {
         $idToRemove = 'uuid';
      } else {
         // Convert IDs into UUIDs
         $export = $this->convertTags($export);
         $questionLinks = [
            'type_rule'          => ['values' => self::REQUESTTYPE_ANSWER, 'field' => 'type_question'],
            'due_date_rule'      => ['values' => self::DUE_DATE_RULE_ANSWER, 'field' => 'due_date_question'],
            'urgency_rule'       => ['values' => self::URGENCY_RULE_ANSWER, 'field' => 'urgency_question'],
            'tag_type'           => ['values' => self::TAG_TYPE_QUESTIONS, 'field' => 'tag_questions'],
            'category_rule'      => ['values' => self::CATEGORY_RULE_ANSWER, 'field' => 'category_question'],
            'associate_rule'     => ['values' => self::ASSOCIATE_RULE_ANSWER, 'field' => 'associate_question'],
            'location_rule'      => ['values' => self::LOCATION_RULE_ANSWER, 'field' => 'location_question'],
            'destination_entity' => [
               'values' => [
                  self::DESTINATION_ENTITY_ENTITY,
                  self::DESTINATION_ENTITY_USER,
               ],
               'field' => 'destination_entity_value'
            ],
         ];
         foreach ($questionLinks as $field => $fieldSetting) {
            if (!is_array($fieldSetting['values'])) {
               $fieldSetting['values'] = [$fieldSetting['values']];
            }
            if (!in_array($export[$field], $fieldSetting['values'])) {
               continue;
            }
            $question = new PluginFormcreatorQuestion();
            $question->getFromDB($export[$fieldSetting['field']]);
            $export[$fieldSetting['field']] = $question->fields['uuid'];
         }
      }
      unset($export[$idToRemove]);

      return $export;
   }

   /**
    * get all target tickets for a form
    *
    * @param int $formId
    * @return array
    */
   public function getTargetsForForm($formId) {
      global $DB;

      $targets = [];
      $rows = $DB->request([
         'SELECT' => ['id'],
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'plugin_formcreator_forms_id' => $formId
         ],
      ]);
      foreach ($rows as $row) {
         $target = new self();
         $target->getFromDB($row['id']);
         $targets[$row['id']] = $target;
      }

      return $targets;
   }
}
