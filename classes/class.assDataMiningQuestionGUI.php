<?php

require_once 'class.calculateEvalMeasure.php';

/**
 * GUI class for data mining question.
 *
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 * @version $Id:  $
 * @ingroup ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assDataMiningQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 */
class assDataMiningQuestionGUI extends assQuestionGUI
{

    /**
     * @var ilassDataMiningPlugin    The plugin object
     */
    public $plugin = null;


    /**
     * @var assDataMining    The question object
     */
    public $object = null;

    /**
    * Constructor
    *
    * @param integer $id The database id of a question object
    * @access public
    */
    public function __construct($id = -1)
    {

        parent::__construct();

        $this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", "assDataMiningQuestion");
        $this->plugin->includeClass("class.assDataMiningQuestion.php");
        $this->object = new assDataMiningQuestion();
        if ($id >= 0) {
            $this->object->loadFromDb($id);
        }
    }


    /**
     * Creates an output of the edit form for the question
     *
     * @param bool $checkonly
     * @return bool
     */
    public function editQuestion($checkonly = false)
    {
        //global $DIC;
        //$lng = $DIC->language();

        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setMultipart(true);
        $form->setTableWidth("100%");
        $form->setId("exmqst");

        // Title, author, description, question, working time
        $this->addBasicQuestionFormProperties($form);
        $this->populateQuestionSpecificFormPart($form);

        $this->populateTaxonomyFormSection($form);
        $this->addQuestionFormCommandButtons($form);

        $errors = false;
        if ($this->isSaveCommand()) {
            $form->setValuesByPost();
            $errors = !$form->checkInput();
            $form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes

            if ($errors) {
                $checkonly = false;
            }
        }

        if (!$checkonly) {
            $this->getQuestionTemplate();
            $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
        }

        return $errors;
    }
    
    /**
     * Here you can add question type specific form properties
     *
     * @param bool $always
     * @return integer A positive value, if one of the required fields wasn't set, else 0
     */
    public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form)
    {
        $trainingFile = new ilFileInputGUI($this->plugin->txt('training_file'), 'trainingFile');
        $trainingFile->setInfo($this->plugin->txt("training_file_info"));
        $trainingFile->setRequired(false);
        $trainingFile->setValue($this->object->getTrainingFileLink());
        $form->addItem($trainingFile);

        $testFeatureFile = new ilFileInputGUI($this->plugin->txt('test_feature_file'), 'testFeatureFile');
        $testFeatureFile->setInfo($this->plugin->txt("test_feature_file_info"));
        $testFeatureFile->setRequired(false);
        $testFeatureFile->setValue($this->object->getTestFeatureFileLink());
        $form->addItem($testFeatureFile);

        $testTargetFile = new ilFileInputGUI($this->plugin->txt('test_target_file'), 'testTargetFile');
        $testTargetFile->setInfo($this->plugin->txt("test_target_file_info"));
        if ($this->object->gettestTargetFileName() == "") {
            $testTargetFile->setRequired(true);
        }
        $testTargetFile->setValue($this->object->getTestTargetFileLink());
        $form->addItem($testTargetFile);
        
        $skipLine = new ilCheckboxInputGUI($this->plugin->txt('skip_first_line'), 'skipFirstLine');
        $skipLine->setInfo($this->plugin->txt("skip_first_line_info"));
        $skipLine->setValue(1);
        $skipLine->setChecked($this->object->getSkipFirstLine());
        $form->addItem($skipLine);

        $eval = new ilRadioGroupInputGUI($this->plugin->txt('eval'), 'eval');
        $eval->setValue($this->object->getEvaluationMethod());
        $eval->setRequired(true);

        $eval->addOption(new ilRadioOption($this->plugin->txt('eval_accuracy'), 'accuracy'));
        foreach (calculateEvalMeasure::$CLASSIFICATION_METHODS as $labelValue => $labelText) {
            //generate average choice for classification
            $average = new ilRadioGroupInputGUI($this->plugin->txt('average'), $labelValue . '_average');
            $average->setRequired(true);
            $average->setValue($this->object->getEvaluationAverage());
            $average->addOption(new ilRadioOption($this->plugin->txt('average_micro'), 'micro'));
            $average->addOption(new ilRadioOption($this->plugin->txt('average_macro'), 'macro'));
            $average->addOption(new ilRadioOption($this->plugin->txt('average_weighted'), 'weighted'));
            $average_binary = new ilRadioOption($this->plugin->txt('average_binary'), 'binary');
            $positive_label = new ilTextInputGUI($this->plugin->txt('positive_label'), $labelValue . '_positive_label');
            $positive_label->setRequired(true);
            $positive_label->setValue($this->object->getEvaluationPosLabel());
            $positive_label->setMaxLength(128);
            $average_binary->addSubItem($positive_label);
            $average->addOption($average_binary);
            
            $measure = new ilRadioOption($this->plugin->txt($labelText), $labelValue);
            $measure->addSubItem($average);
            $eval->addOption($measure);
        }
        
        foreach (calculateEvalMeasure::$REGRESSION_METHODS as $labelValue => $labelText) {
            $min_value = new ilNumberInputGUI($this->plugin->txt('eval_min_value'), $labelValue . '_min_value');
            $min_value->setRequired(true);
            $min_value->setValue($this->object->getEvaluationMin());
            $min_value->setInfo($this->plugin->txt('eval_min_value_info'));
            $max_value = new ilNumberInputGUI($this->plugin->txt('eval_max_value'), $labelValue . '_max_value');
            $max_value->setRequired(true);
            $max_value->setValue($this->object->getEvaluationMax());
            $max_value->setInfo($this->plugin->txt('eval_max_value_info'));
            
            $measure = new ilRadioOption($this->plugin->txt($labelText), $labelValue);
            $measure->addSubItem($min_value);
            $measure->addSubItem($max_value);
            $eval->addOption($measure);
        }
        
        //BEGIN - if you want to disallow the external evaluation, comment the following lines
        $evalCustom = new ilRadioOption($this->plugin->txt('eval_custom'), 'custom');
        $externalServiceURL = new ilTextInputGUI($this->plugin->txt('eval_custom_url'), 'custom_external_URL');
        $externalServiceURL->setMaxLength(512);
        $externalServiceURL->setValidationRegexp("@^https?://[^\s/$.?#].[^\s]*$@iS");
        $externalServiceURL->setValidationFailureMessage($this->plugin->txt('eval_custom_url_wrong_format'));
        $externalServiceURL->setRequired(true);
        $externalServiceURL->setInfo($this->plugin->txt('eval_custom_url_info'));
        $externalServiceURL->setValue($this->object->getEvaluationUrl());
        $evalCustom->addSubItem($externalServiceURL);
        $eval->addOption($evalCustom);
        //END - if you want to disallow the external evaluation, comment up to here

        $form->addItem($eval);

        $solutionFileSuffix = new ilTextInputGUI($this->plugin->txt('file_suffix'), 'solutionFileSuffix');
        $solutionFileSuffix->setMaxLength(512);
        $solutionFileSuffix->setInfo($this->plugin->txt('file_suffix_info'));
        $solutionFileSuffix->setValue($this->object->getSolutionFileSuffix());
        $form->addItem($solutionFileSuffix);

        // max upload size
        $maxsize = new ilNumberInputGUI($this->plugin->txt('max_size'), 'maxsize');
        $maxsize->setInfo($this->plugin->txt('maxsize_info'));
        $maxsize->setSize(10);
        $maxsize->setValue($this->object->getMaxSize() == 0 ? 0 : $this->object->getMaxSize() / 1024);
        $maxsize->allowDecimals(false);
        $maxsize->setMinValue(1);
        $maxsize->setMaxValue($this->object->determineMaxFilesizeInIlias() / 1024, true);//second parameter shows the maximum value
        $maxsize->setRequired(true);
        $form->addItem($maxsize);

        // points
        $points = new ilNumberInputGUI($this->lng->txt("points"), "points");
        $points->allowDecimals(true);
        $points->setValue(
            is_numeric($this->object->getPoints()) && $this->object->getPoints() >= 0 ? $this->object->getPoints() : ''
        );
        $points->setRequired(true);
        $points->setSize(3);
        $points->setMinValue(1);
        $points->setMinvalueShouldBeGreater(false);
        $form->addItem($points);

        return $form;
    }


    /**
     * Evaluates a posted edit form and writes the form data in the question object
     *
     * @param bool $always
     * @return integer A positive value, if one of the required fields wasn't set, else 0
     */
    public function writePostData($always = false)
    {
        $hasErrors = (!$always) ? $this->editQuestion(false) : false;
        if ($hasErrors) {
            return 1;
        }
        try {
            //If no errors appeared up to now, make final check:
            $this->finalCheckOfFormInput();
            
            $this->writeQuestionGenericPostData();
            $this->writeQuestionSpecificPostData();
            $this->saveTaxonomyAssignments();
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            return 1;
        }
        return 0;
    }
    
    private function finalCheckOfFormInput()
    {
        $evalMethod = $_POST["eval"];
        $removeHeader = $_POST['skipFirstLine'] == 1 ? true : false;
        $maxPoints = $_POST["points"];
        $average = $_POST[$_POST["eval"] . "_average"];
        $posLabel = $_POST[$_POST["eval"] . "_positive_label"];
        $min = $_POST[$_POST["eval"] . "_min_value"];
        $max = $_POST[$_POST["eval"] . "_max_value"];
        $url = $_POST['custom_external_URL'];
        $gold = $this->object->getTestTargetFilePath(true);
        if (!empty($_FILES['testTargetFile']['tmp_name'])) {
            $gold = $_FILES['testTargetFile']['tmp_name'];
        }

        $pointDescription = calculateEvalMeasure::calculate($evalMethod, $removeHeader, $maxPoints, $average, $posLabel, $min, $max, $url, $gold);
        ilUtil::sendInfo($pointDescription->getDescription(), true);
    }

    public function writeQuestionSpecificPostData()
    {
        if (!empty($_FILES['trainingFile']['tmp_name'])) {
            $this->object->setTrainingFileName($_FILES['trainingFile']['name'], $_FILES['trainingFile']['tmp_name']);
        }
        if (!empty($_FILES['testFeatureFile']['tmp_name'])) {
            $this->object->setTestFeatureFileName($_FILES['testFeatureFile']['name'], $_FILES['testFeatureFile']['tmp_name']);
        }
        if (!empty($_FILES['testTargetFile']['tmp_name'])) {
            $this->object->setTestTargetFileName($_FILES['testTargetFile']['name'], $_FILES['testTargetFile']['tmp_name']);
        }
        
        $this->object->setSkipFirstLine($_POST['skipFirstLine'] == 1 ? true : false);
        $this->object->setEvaluationMethod($_POST["eval"]);
        
        if (array_key_exists($_POST["eval"], calculateEvalMeasure::$CLASSIFICATION_METHODS)) {
            $this->object->setEvaluationAverage($_POST[$_POST["eval"] . "_average"]);
            $this->object->setEvaluationPosLabel($_POST[$_POST["eval"] . "_positive_label"]);
        }
        if (array_key_exists($_POST["eval"], calculateEvalMeasure::$REGRESSION_METHODS)) {
            $this->object->setEvaluationMin($_POST[$_POST["eval"] . "_min_value"]);
            $this->object->setEvaluationMax($_POST[$_POST["eval"] . "_max_value"]);
        }
        $this->object->setEvaluationUrl($_POST['custom_external_URL']);
        $this->object->setSolutionFileSuffix(join(",", $this->object->parseFileSuffix($_POST["solutionFileSuffix"])));
        $this->object->setMaxSize($_POST["maxsize"] * 1024);
        $this->object->setPoints($_POST["points"]);
    }


    /**
     * @return ilPropertyFormGUI
     */
    protected function buildEditForm()
    {
        $form = $this->buildBasicEditFormObject();

        $this->addBasicQuestionFormProperties($form);
        $this->populateQuestionSpecificFormPart($form);
        $this->populateTaxonomyFormSection($form);
        $this->addQuestionFormCommandButtons($form);

        return $form;
    }

    /**
     * Get the HTML output of the question for a test
     * (this function could be private)
     *
     * @param integer $active_id                        The active user id
     * @param integer $pass                                The test pass
     * @param boolean $is_postponed                        Question is postponed
     * @param boolean $use_post_solutions                Use post solutions
     * @param boolean $show_specific_inline_feedback    Show a specific inline feedback
     * @return string
     */
    public function getTestOutput($active_id, $pass = null, $is_postponed = false, $use_post_solutions = false, $show_specific_inline_feedback = false)
    {
        if (is_null($pass)) {
            $pass = ilObjTest::_getPass($active_id);
        }
        $solution = $this->object->getSolutionStored($active_id, $pass, null);
        $questionoutput = $this->renderTestOutput($solution);
        $pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
        return $pageoutput;
    }

    
    /**
     * Get the output for question preview
     * (called from ilObjQuestionPoolGUI)
     *
     * @param boolean    $show_question_only     show only the question instead of embedding page (true/false)
     * @param boolean    $show_question_only
     * @return string
     */
    public function getPreview($show_question_only = false, $showInlineFeedback = false)
    {
        if (is_object($this->getPreviewSession())) {
            $solution = $this->getPreviewSession()->getParticipantsSolution();
        } else {
            $solution = array('value1' => null, 'value2' => null);
        }
        $questionoutput = $this->renderTestOutput($solution);
        if (!$show_question_only) {
            // get page object output
            $questionoutput = $this->getILIASPage($questionoutput);
        }
        return $questionoutput;
    }
    
    private function renderTestOutput($solution)
    {
        //returns questionoutput
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_datamining_output.html");
        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($this->object->question, true));
        $template->setVariable("TXT_MAX_SIZE", $this->object->prepareTextareaOutput($this->plugin->txt('maximum_file_size_info') . " " . $this->object->getMaxSizeAsString()));
        $template->setVariable("CMD_UPLOAD", $this->getQuestionActionCmd());
        
        if ($this->object->getTrainingFileLink() != "") {
            $template->setVariable("TRAINING_LINK", $this->object->prepareTextareaOutput($this->plugin->txt('training_data_info') . ": " .  $this->object->getTrainingFileLink()));
        }
        if ($this->object->getTestFeatureFileLink() != "") {
            $template->setVariable("TEST_FEATURE_LINK", $this->object->prepareTextareaOutput($this->plugin->txt('test_feature_data_info') . ": " . $this->object->getTestFeatureFileLink()));
        }
        if ($this->object->getSolutionFileSuffix() != "") {
            $template->setCurrentBlock("allowed_extensions");
            $template->setVariable("TXT_ALLOWED_EXTENSIONS", $this->object->prepareTextareaOutput($this->plugin->txt("allowed_extensions_info") . ": " . $this->object->getSolutionFileSuffix()));
            $template->setVariable("ALLOWED_EXTENSIONS", ' accept="' . $this->object->getSolutionFileSuffix() . '"');
            $template->parseCurrentBlock();
        }
        
        if ($solution['value1']) {
            $template->setCurrentBlock("last_upload");
            $template->setVariable("TXT_LAST_UPLOAD", $this->object->prepareTextareaOutput($this->plugin->txt("already_uploaded_info")));
            $template->setVariable("LAST_UPLOAD_LINK", $this->object->createFilePathFromFragment($solution['value1']));
            $template->setVariable("LAST_UPLOAD_NAME", $solution['value2']);
            $template->setVariable("LAST_UPLOAD_INFO", "(" . $solution['date'] . " " . $this->object->formatFileSize($solution['size']) . ")");
            $template->parseCurrentBlock();
            $template->setVariable("TXT_UPLOAD_BUTTON", $this->plugin->txt("file_upload_button_override"));
        } else {
            $template->setVariable("TXT_UPLOAD_BUTTON", $this->plugin->txt("file_upload_button"));
        }
        return $template->get();
    }

    /**
     * Get the question solution output
     * @param integer $active_id             The active user id
     * @param integer $pass                  The test pass
     * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
     * @param boolean $result_output         Show the reached points for parts of the question
     * @param boolean $show_question_only    Show the question without the ILIAS content around
     * @param boolean $show_feedback         Show the question feedback
     * @param boolean $show_correct_solution Show the correct solution instead of the user solution
     * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
     * @param bool    $show_question_text

     * @return string solution output of the question as HTML code
     */
    public function getSolutionOutput(
        $active_id,
        $pass = null,
        $graphicalOutput = false,
        $result_output = false,
        $show_question_only = true,
        $show_feedback = false,
        $show_correct_solution = false,
        $show_manual_scoring = false,
        $show_question_text = true
    ) {
        // get the solution of the user for the active pass or from the last pass if allowed
        if($show_correct_solution){
            $value1 = $this->object->getQuestionFilePathFragment() . $this->object->getTestTargetFileName();
            $value2 = $this->plugin->txt("best_solution_file_name");
            $date = "";
            $filesize = 0;
        }else if ($active_id > 0) {
            $solution = $this->object->getSolutionStored($active_id, $pass, true);
            $value1 = isset($solution["value1"]) ? $solution["value1"] : "";
            $value2 = isset($solution["value2"]) ? $solution["value2"] : "";
            $date = isset($solution["date"]) ? $solution["date"] : "";
            $filesize = isset($solution["size"]) ? $solution["size"] : "";
        } else {
            $value1 = "";
            $value2 = "";
            $date = "";
            $filesize = 0;
        }

        // get the solution template
        $template = $this->plugin->getTemplate("tpl.il_as_qpl_datamining_solution.html");

        // fill the template variables
        if ($show_question_text == true) {
            $template->setCurrentBlock("question_text");
            $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($this->object->getQuestion(), true));
            $template->parseCurrentBlock();
        }
        
        if ($value1) {
            $template->setCurrentBlock("last_upload");
            $template->setVariable("TXT_LAST_UPLOAD", $this->object->prepareTextareaOutput($this->plugin->txt("already_uploaded_info")));
            $template->setVariable("LAST_UPLOAD_LINK", $this->object->createFilePathFromFragment($value1));
            $template->setVariable("LAST_UPLOAD_NAME", $value2);
            if($date != "" || $filesize != 0){
                $template->setVariable("LAST_UPLOAD_INFO", "(" . $date . " " . $this->object->formatFileSize($filesize) . ")");
            }
            $template->parseCurrentBlock();
            $desc = "";
            try {
                $systemFilePath = $this->object->createFilePathFromFragment($value1, true);
                $pointDescription = calculateEvalMeasure::calculateObject($this->object, $systemFilePath);
                $desc = $pointDescription->getDescription();
            } catch (Exception $e) {
                ilUtil::sendFailure($e->getMessage(), true);
            }
            $template->setVariable("TXT_DESCRIPTION", $desc);
        }
        $solutionoutput = $template->get();
        if (!$show_question_only) {
            // get page object output
            $solutionoutput = $this->getILIASPage($solutionoutput);
        }
        return $solutionoutput;
    }

    /**
     * Returns the answer specific feedback for the question
     *
     * @param integer $userSolution Active pass
     * @param integer $old in ilias5.3 was pass
     * @return string HTML Code with the answer specific feedback
     * @access public
     */
     public function getSpecificFeedbackOutput($userSolution, $old=NULL){
         //in Ilias 5.3 and before: getSpecificFeedbackOutput($active_id, $pass)
         //in Ilias 5.4 and after : getSpecificFeedbackOutput($userSolution)
        return $this->object->prepareTextareaOutput('', true);
     }
    
    
    /**
    * Sets the ILIAS tabs for this question type
    * called from ilObjTestGUI and ilObjQuestionPoolGUI
    */
    public function setQuestionTabs()
    {
        global $DIC;
        $rbacsystem = $DIC->rbac()->system();
        $ilTabs = $DIC->tabs();

        $this->ctrl->setParameterByClass("ilpageobjectgui", "q_id", $_GET["q_id"]);
        include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
        $q_type = $this->object->getQuestionType();

        if (strlen($q_type)) {
            $classname = $q_type . "GUI";
            $this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
            $this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
        }

        if ($_GET["q_id"]) {
            if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
                // edit page
                $ilTabs->addTarget(
                    "edit_page",
                    $this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
                    array("edit", "insert", "exec_pg"),
                    "",
                    "",
                    $force_active
                );
            }

            $this->addTab_QuestionPreview($ilTabs);
        }

        $force_active = false;
        if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
            $url = "";

            if ($classname) {
                $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
            }
            $commands = $_POST["cmd"];

            // edit question properties
            $ilTabs->addTarget(
                "edit_properties",
                $url,
                array("editQuestion", "save", "cancel", "saveEdit", "originalSyncForm"),
                $classname,
                "",
                $force_active
            );
        }

        // add tab for question feedback within common class assQuestionGUI
        $this->addTab_QuestionFeedback($ilTabs);

        // add tab for question hint within common class assQuestionGUI
        $this->addTab_QuestionHints($ilTabs);

        // add tab for question's suggested solution within common class assQuestionGUI
        $this->addTab_SuggestedSolution($ilTabs, $classname);


        // Assessment of questions sub menu entry
        if ($_GET["q_id"]) {
            $ilTabs->addTarget(
                "statistics",
                $this->ctrl->getLinkTargetByClass($classname, "assessment"),
                array("assessment"),
                $classname,
                ""
            );
        }

        $this->addBackTab($ilTabs);
    }

    public function getFormEncodingType()
    {
        return self::FORM_ENCODING_MULTIPART;
    }
}
