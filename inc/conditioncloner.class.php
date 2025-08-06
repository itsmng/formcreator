<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginFormcreatorConditionCloner
{
    // Default condition values
    private const DEFAULT_SHOW_RULE = PluginFormcreatorCondition::SHOW_RULE_SHOWN;
    private const DEFAULT_SHOW_LOGIC = PluginFormcreatorCondition::SHOW_LOGIC_AND;
    private const DEFAULT_SHOW_CONDITION = PluginFormcreatorCondition::SHOW_CONDITION_EQ;
    private const DEFAULT_SHOW_VALUE = '';

    /**
     * Clone conditions from old form to new form.
     * This will remap questions based on their position in the section.
     * If a question is not found in the new form, it will not clone its conditions.
     *
     * @param int $oldFormId ID of the original form
     * @param int $newFormId ID of the new form
     */
    public static function cloneForForm(int $oldFormId, int $newFormId)
    {
        // Behaviour:
        //  - Get sections ordered by position in both forms
        //  - Build a mapping of old section IDs to new section IDs based on their position
        //  - For each section, get questions ordered by position
        //  - Build a mapping of old question IDs to new question IDs based on their position
        //  - Clone conditions for each mapped question
        global $DB;
        try {
            $oldSections = self::getSectionsByOrder($oldFormId);
            $newSections = self::getSectionsByOrder($newFormId);

            $sectionPairs = [];
            $secCount = min(count($oldSections), count($newSections));
            for ($i = 0; $i < $secCount; $i++) {
                $sectionPairs[] = [$oldSections[$i], $newSections[$i]];
            }
            
            $qMap = [];
            foreach ($sectionPairs as [$oldSectionId, $newSectionId]) {
                $oldQs = self::getQuestionsByPosition($oldSectionId);
                $newQs = self::getQuestionsByPosition($newSectionId);
                $qCount = min(count($oldQs), count($newQs));
                for ($i = 0; $i < $qCount; $i++) {
                    $qMap[$oldQs[$i]] = $newQs[$i];
                }
            }

            foreach ($qMap as $oldQ => $newQ) {
                self::cloneConditionsForQuestion($oldQ, $newQ, $qMap);
            }
        } catch (\Throwable $e) {
            Toolbox::logError(
                "Failed to clone conditions for form $oldFormId to $newFormId: " . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Clone conditions for a section.
     * This will remap questions based on their position in the section.
     * If a question is not found in the new section, it will not clone its conditions.
     *
     * @param int $oldSectionId ID of the original section
     * @param int $newSectionId ID of the new section
     */
    public static function cloneForSection(int $oldSectionId, int $newSectionId)
    {
        // Behaviour:
        //  - Get questions ordered by position in both sections
        //  - Build a mapping of old question IDs to new question IDs based on their position
        //  - Clone conditions for each mapped question
        try {
            $oldQuestions = self::getQuestionsByPosition($oldSectionId);
            $newQuestions = self::getQuestionsByPosition($newSectionId);

            $qMap = self::buildQuestionMapping($oldQuestions, $newQuestions);

            foreach ($qMap as $oldQ => $newQ) {
                self::cloneConditionsForQuestion($oldQ, $newQ, $qMap);
            }
        } catch (\Throwable $e) {
            Toolbox::logError(
                "Failed to clone conditions for section $oldSectionId to $newSectionId: " . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Clone conditions for a question.
     * This will remap self-references to the new question ID.
     * If no conditions are found via the primary method, it will use a fallback approach.
     *
     * @param PluginFormcreatorQuestion $question The original question object
     * @param int $newQuestionId ID of the new question
     */
    public static function cloneForQuestion($question, int $newQuestionId)
    {
        // Behaviour:
        //  - Get conditions for the original question
        //  - Clone each condition with remapped references
        //  - Only use fallback if no conditions were successfully cloned
        try {
            $originalId = (int) $question->getID();
            $originalConds = self::getConditionsForQuestion($originalId);
            $clonedCount = 0;

            foreach ($originalConds as $cond) {
                $f = $cond->fields;

                if (!isset($f['items_id']) || (int) $f['items_id'] !== $originalId) {
                    continue;
                }

                $payload = self::buildConditionPayload($f, $newQuestionId);

                // Handle self-references (simpler case for single question)
                if (isset($f['plugin_formcreator_questions_id']) && (int) $f['plugin_formcreator_questions_id'] > 0) {
                    $ref = (int) $f['plugin_formcreator_questions_id'];
                    $payload['plugin_formcreator_questions_id'] = ($ref === $originalId) ? $newQuestionId : $ref;
                }

                if ((new PluginFormcreatorCondition())->add($payload)) {
                    $clonedCount++;
                }
            }

            // Only use fallback if no conditions were successfully cloned
            if ($clonedCount === 0) {
                self::fallbackConditionCloning($originalId, $newQuestionId);
            }
        } catch (\Throwable $e) {
            Toolbox::logError(
                "Failed to clone conditions for question $originalId to $newQuestionId: " . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * Get sections ordered by their order field
     *
     * @param int $formId
     * @return array Array of section IDs
     */
    private static function getSectionsByOrder(int $formId): array
    {
        global $DB;
        $rows = $DB->request([
            'SELECT' => ['id'],
            'FROM' => PluginFormcreatorSection::getTable(),
            'WHERE' => [PluginFormcreatorForm::getForeignKeyField() => $formId],
            'ORDER' => ['order ASC']
        ]);
        $list = [];
        foreach ($rows as $r) {
            $list[] = (int) $r['id'];
        }
        return $list;
    }

    /**
     * Get questions in a section ordered by row and column
     *
     * @param int $sectionId
     * @return array Array of question IDs
     */
    private static function getQuestionsByPosition(int $sectionId): array
    {
        global $DB;
        $rows = $DB->request([
            'SELECT' => ['id'],
            'FROM' => PluginFormcreatorQuestion::getTable(),
            'WHERE' => [PluginFormcreatorSection::getForeignKeyField() => $sectionId],
            'ORDER' => ['row ASC', 'col ASC']
        ]);
        $list = [];
        foreach ($rows as $r) {
            $list[] = (int) $r['id'];
        }
        return $list;
    }

    /**
     * Build question mapping based on positional order
     *
     * @param array $oldQuestions Array of old question IDs
     * @param array $newQuestions Array of new question IDs
     * @return array Question mapping (oldId => newId)
     */
    private static function buildQuestionMapping(array $oldQuestions, array $newQuestions): array
    {
        $qMap = [];
        $count = min(count($oldQuestions), count($newQuestions));
        for ($i = 0; $i < $count; $i++) {
            $qMap[$oldQuestions[$i]] = $newQuestions[$i];
        }
        return $qMap;
    }

    /**
     * Get conditions for a question with fallback to raw DB query
     *
     * @param int $questionId
     * @return array Array of PluginFormcreatorCondition objects
     */
    private static function getConditionsForQuestion(int $questionId): array
    {
        global $DB;
        
        // Try to get conditions via model
        $condModel = new PluginFormcreatorCondition();
        $oldQObj = new PluginFormcreatorQuestion();
        if (!$oldQObj->getFromDB($questionId)) {
            return [];
        }
        $conds = $condModel->getConditionsFromItem($oldQObj);

        // Fallback to raw DB if model returns none
        if (!is_array($conds) || count($conds) === 0) {
            $condTable = PluginFormcreatorCondition::getTable();
            $raw = iterator_to_array($DB->request([
                'FROM' => $condTable,
                'WHERE' => [
                    'items_id' => $questionId,
                ],
            ]));
            $conds = [];
            foreach ($raw as $row) {
                $c = new PluginFormcreatorCondition();
                $c->fields = $row;
                $conds[] = $c;
            }
        }

        return $conds;
    }

    /**
     * Build condition payload with default values
     *
     * @param array $conditionFields Original condition fields
     * @param int $newQuestionId New question ID
     * @return array Condition payload
     */
    private static function buildConditionPayload(array $conditionFields, int $newQuestionId): array
    {
        return [
            'itemtype' => PluginFormcreatorQuestion::class,
            'items_id' => $newQuestionId,
            'show_rule' => (int) ($conditionFields['show_rule'] ?? self::DEFAULT_SHOW_RULE),
            'show_logic' => (int) ($conditionFields['show_logic'] ?? self::DEFAULT_SHOW_LOGIC),
            'show_condition' => (int) ($conditionFields['show_condition'] ?? self::DEFAULT_SHOW_CONDITION),
            'show_value' => (string) ($conditionFields['show_value'] ?? self::DEFAULT_SHOW_VALUE),
            //'_skip_checks' => true,
        ];
    }

    /**
     * Remap question reference in condition payload
     *
     * @param array $payload Condition payload
     * @param array $conditionFields Original condition fields
     * @param int $oldQuestionId Old question ID
     * @param int $newQuestionId New question ID
     * @param array $questionMap Question mapping array
     * @return array Updated payload
     */
    private static function remapQuestionReference(array $payload, array $conditionFields, int $oldQuestionId, int $newQuestionId, array $questionMap): array
    {
        if (isset($conditionFields['plugin_formcreator_questions_id']) && (int) $conditionFields['plugin_formcreator_questions_id'] > 0) {
            $ref = (int) $conditionFields['plugin_formcreator_questions_id'];
            // Self-reference
            if ($ref === $oldQuestionId) {
                $payload['plugin_formcreator_questions_id'] = $newQuestionId;
            } else if (isset($questionMap[$ref])) {
                // Cross-question reference within the form -> remap to corresponding new question
                $payload['plugin_formcreator_questions_id'] = $questionMap[$ref];
            } else {
                // Keep original reference if we can't map it safely
                $payload['plugin_formcreator_questions_id'] = $ref;
            }
        }
        return $payload;
    }

    /**
     * Clone conditions for a single question with question mapping
     *
     * @param int $oldQuestionId
     * @param int $newQuestionId
     * @param array $questionMap
     */
    private static function cloneConditionsForQuestion(int $oldQuestionId, int $newQuestionId, array $questionMap): void
    {
        $conds = self::getConditionsForQuestion($oldQuestionId);

        foreach ($conds as $cond) {
            $f = $cond->fields;
            if (!isset($f['items_id']) || (int) $f['items_id'] !== $oldQuestionId) {
                continue;
            }

            $payload = self::buildConditionPayload($f, $newQuestionId);
            
            $payload = self::remapQuestionReference($payload, $f, $oldQuestionId, $newQuestionId, $questionMap);

            (new PluginFormcreatorCondition())->add($payload);
        }
    }

    /**
     * Fallback condition cloning for cases where primary method fails.
     * Includes safety check to prevent duplicate conditions.
     *
     * @param int $originalId
     * @param int $newQuestionId
     */
    private static function fallbackConditionCloning(int $originalId, int $newQuestionId): void
    {
        global $DB;
        
        // Dedupe
        $existingConditions = $DB->request([
            'FROM' => PluginFormcreatorCondition::getTable(),
            'WHERE' => [
                'items_id' => $newQuestionId,
                'itemtype' => PluginFormcreatorQuestion::class,
            ],
        ]);
        
        if (count($existingConditions) > 0) {
            return;
        }
        
        $condTable = PluginFormcreatorCondition::getTable();
        $raw = iterator_to_array($DB->request([
            'FROM' => $condTable,
            'WHERE' => [
                'items_id' => $originalId,
                'itemtype' => PluginFormcreatorQuestion::class,
            ],
        ]));
        
        foreach ($raw as $f) {
            $payload = self::buildConditionPayload($f, $newQuestionId);

            if (isset($f['plugin_formcreator_questions_id']) && (int) $f['plugin_formcreator_questions_id'] > 0) {
                $ref = (int) $f['plugin_formcreator_questions_id'];
                $payload['plugin_formcreator_questions_id'] = ($ref === $originalId) ? $newQuestionId : $ref;
            }
            
            (new PluginFormcreatorCondition())->add($payload);
        }
    }
}
