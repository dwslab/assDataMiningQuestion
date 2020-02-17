<?php

require_once 'class.calculateEvalMeasure.php';
require_once 'class.fileSizePreProcessor.php';

use ILIAS\FileUpload\Location;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\Processor\VirusScannerPreProcessor;
use ILIAS\FileUpload\Processor\WhitelistExtensionPreProcessor;
use ILIAS\Data\DataSize;
use ILIAS\Filesystem\Visibility;

/**
 * Data mining question class which stores all attributes for one question.
 *
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 * @ingroup ModulesTestQuestionPool
 */

class assDataMiningQuestion extends assQuestion
{
    protected $plugin = null;
    private $training_file_name;
    private $test_feature_file_name;
    private $test_target_file_name;
    private $skip_first_line;
    private $evaluation_method;
    private $evaluation_average;
    private $evaluation_pos_label;
    private $evaluation_min;
    private $evaluation_max;
    private $evaluation_url;
    private $solution_file_suffix;
    private $maxsize; //in bytes

    /**
     * Constructor
     *
     * The constructor takes possible arguments and creates an instance of the question object.
     *
     * @param string $title A title string to describe the question
     * @param string $comment A comment string to describe the question
     * @param string $author A string containing the name of the questions author
     * @param integer $owner A numerical ID to identify the owner/creator
     * @param string $question Question text
     * @param string $training_file_name
     * @param string $test_feature_file_name
     * @param string $test_target_file_name
     * @param boolean $skip_first_line
     * @param string $evaluation_method
     * @param string $evaluation_average
     * @param string $evaluation_pos_label
     * @param float $evaluation_min
     * @param float $evaluation_max
     * @param string $evaluation_url
     * @param string $solution_file_suffix
     * @param integer maxsize
     * @access public
     *
     * @see assQuestion:assQuestion()
     */
    public function __construct(
        $title = '',
        $comment = '',
        $author = '',
        $owner = -1,
        $question = '',
        $training_file_name = '',
        $test_feature_file_name = '',
        $test_target_file_name = '',
        $skip_first_line = true,
        $evaluation_method = '',
        $evaluation_average = '',
        $evaluation_pos_label = '',
        $evaluation_min = 0.0,
        $evaluation_max = 0.0,
        $evaluation_url = '',
        $solution_file_suffix = '',
        $maxsize = 500
    ) {
        // needed for excel export
        $this->getPlugin()->loadLanguageModule();

        parent::__construct($title, $comment, $author, $owner, $question);
        $this->training_file_name = $training_file_name;
        $this->test_feature_file_name = $test_feature_file_name;
        $this->test_target_file_name = $test_target_file_name;
        $this->skip_first_line = $skip_first_line;
        $this->evaluation_method = $evaluation_method;
        $this->evaluation_average = $evaluation_average;
        $this->evaluation_pos_label = $evaluation_pos_label;
        $this->evaluation_min = $evaluation_min;
        $this->evaluation_max = $evaluation_max;
        $this->evaluation_url = $evaluation_url;
        $this->solution_file_suffix = $solution_file_suffix;
        $this->maxsize = $maxsize;
    }


    /**
     * Returns the question type of the question
     *
     * @return string The question type of the question
     */
    public function getQuestionType()
    {
        return 'assDataMiningQuestion';
    }

    /**
     * Returns the names of the additional question data tables
     *
     * All tables must have a 'question_fi' column.
     * Data from these tables will be deleted if a question is deleted
     *
     * @return mixed     the name(s) of the additional tables (array or string)
     */
    public function getAdditionalTableName()
    {
        return 'qpl_qst_datamining';
    }

    /**
     * Collects all texts in the question which could contain media objects
     * which were created with the Rich Text Editor
     */
    public function getRTETextWithMediaObjects()
    {
        $text = parent::getRTETextWithMediaObjects();

        // eventually add the content of question type specific text fields
        // ..

        return $text;
    }


    /********************
    * SETTER and GETTER
    *********************/
    

    public function setTrainingFileName($training_file_name, $temp_filename = "")
    {
        if ($temp_filename != "") {
            //first delete old file:
            $this->deleteQuestionFile($this->training_file_name);
            $training_file_name = ilFileUtils::getValidFilename($training_file_name);
            $this->moveUploadedQuestionFile($temp_filename, $training_file_name);
        }
        $this->training_file_name = $training_file_name;
    }
    public function getTrainingFileName()
    {
        return $this->training_file_name;
    }
    public function getTrainingFilePath($absolute = false)
    {
        return $this->createFilePathFromFragment($this->getQuestionFilePathFragment() . $this->training_file_name, $absolute);
    }
    public function getTrainingFileLink()
    {
        return $this->getQuestionFileLink($this->training_file_name);
    }


    public function setTestFeatureFileName($test_feature_file_name, $temp_filename = "")
    {
        if ($temp_filename != "") {
            $this->deleteQuestionFile($this->test_feature_file_name);
            $test_feature_file_name = ilFileUtils::getValidFilename($test_feature_file_name);
            $this->moveUploadedQuestionFile($temp_filename, $test_feature_file_name);
        }
        $this->test_feature_file_name = $test_feature_file_name;
    }
    public function getTestFeatureFileName()
    {
        return $this->test_feature_file_name;
    }
    public function getTestFeatureFilePath($absolute = false)
    {
        return $this->createFilePathFromFragment($this->getQuestionFilePathFragment() . $this->test_feature_file_name, $absolute);
    }
    public function getTestFeatureFileLink()
    {
        return $this->getQuestionFileLink($this->test_feature_file_name);
    }


    public function setTestTargetFileName($test_target_file_name, $temp_filename = "")
    {
        if ($temp_filename != "") {
            $this->deleteQuestionFile($this->test_target_file_name);
            $test_target_file_name = ilFileUtils::getValidFilename($test_target_file_name);
            $this->moveUploadedQuestionFile($temp_filename, $test_target_file_name);
        }
        $this->test_target_file_name = $test_target_file_name;
    }
    public function getTestTargetFileName()
    {
        return $this->test_target_file_name;
    }
    public function getTestTargetFilePath($absolute = false)
    {
        return $this->createFilePathFromFragment($this->getQuestionFilePathFragment() . $this->test_target_file_name, $absolute);
    }
    public function getTestTargetFileLink()
    {
        return $this->getQuestionFileLink($this->test_target_file_name);
    }


    public function setSkipFirstLine($skip_first_line)
    {
        $this->skip_first_line = $skip_first_line;
    }
    public function getSkipFirstLine()
    {
        return $this->skip_first_line;
    }

    public function setEvaluationMethod($evaluation_method)
    {
        $this->evaluation_method = $evaluation_method;
    }
    public function getEvaluationMethod()
    {
        return $this->evaluation_method;
    }
    
    public function setEvaluationAverage($evaluation_average)
    {
        $this->evaluation_average = $evaluation_average;
    }
    public function getEvaluationAverage()
    {
        return $this->evaluation_average;
    }
    
    public function setEvaluationPosLabel($evaluation_pos_label)
    {
        $this->evaluation_pos_label = $evaluation_pos_label;
    }
    public function getEvaluationPosLabel()
    {
        return $this->evaluation_pos_label;
    }
    
    public function setEvaluationMin($evaluation_min)
    {
        $this->evaluation_min = $evaluation_min;
    }
    public function getEvaluationMin()
    {
        return $this->evaluation_min;
    }
    
    public function setEvaluationMax($evaluation_max)
    {
        $this->evaluation_max = $evaluation_max;
    }
    public function getEvaluationMax()
    {
        return $this->evaluation_max;
    }
    
    public function setEvaluationUrl($evaluation_url)
    {
        $this->evaluation_url = $evaluation_url;
    }
    public function getEvaluationUrl()
    {
        return $this->evaluation_url;
    }
    
    public function setSolutionFileSuffix($solution_file_suffix)
    {
        $this->solution_file_suffix = $solution_file_suffix;
    }
    public function getSolutionFileSuffix()
    {
        return $this->solution_file_suffix;
    }
    public function getSolutionFileSuffixArray()
    {
        return $this->parseFileSuffix($this->solution_file_suffix);
    }
    
    public function parseFileSuffix($text)
    {
        $suffixArray = array();
        if (!strlen($text)) {
            return $suffixArray;
        }
        foreach (explode(",", $text) as $suffix) {
            $suffixArray[] = ltrim(trim($suffix), ".");
        }
        return array_filter($suffixArray);
    }
    

    public function setMaxSize($maxsize)
    {
        $this->maxsize = $maxsize;
    }
    public function getMaxSize()
    {
        return $this->maxsize;
    }
    
    /**
    * Return the maximum allowed file size as string (e.g. 1 MB).
    * @return string Maximum allowed file size
    */
    public function getMaxSizeAsString()
    {
        return $this->formatFileSize($this->getMaxSize());//max size is in bytes
    }
    
    public function formatFileSize($sizeInBytes)
    {
        //base is 1000 for KB and 1024 for KiB - see Ilias /src/Data/DataSize.php
        //if ($sizeInBytes < 1000){
        //    return sprintf("%d Bytes",$sizeInBytes);
        //}else
        if ($sizeInBytes < 1000 * 1000) {
            return sprintf("%.1f KB", $sizeInBytes / 1000);
        } elseif ($sizeInBytes < 1000 * 1000 * 1000) {
            return sprintf("%.1f MB", $sizeInBytes / 1000 / 1000);
        } else {
            return sprintf("%.1f GB", $sizeInBytes / 1000 / 1000 / 1000);
        }
    }
    
    /**
    * Returns the maximum file size in bytes which ilias is able to handle.
    * @return int Maximum allowed file size of ilias in bytes
    */
    public function determineMaxFilesizeInIlias()
    {
        // get the value for the maximal uploadable filesize from the php.ini (if available)
        $umf = get_cfg_var("upload_max_filesize");
        // get the value for the maximal post data from the php.ini (if available)
        $pms = get_cfg_var("post_max_size");

        //convert from short-string representation to "real" bytes
        $multiplier_a = array("K" => 1024, "M" => 1024 * 1024, "G" => 1024 * 1024 * 1024);

        $umf_parts = preg_split("/(\d+)([K|G|M])/", $umf, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $pms_parts = preg_split("/(\d+)([K|G|M])/", $pms, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if (count($umf_parts) == 2) {
            $umf = $umf_parts[0] * $multiplier_a[$umf_parts[1]];
        }
        if (count($pms_parts) == 2) {
            $pms = $pms_parts[0] * $multiplier_a[$pms_parts[1]];
        }

        // use the smaller one as limit
        $max_filesize = min($umf, $pms);

        if (!$max_filesize) {
            $max_filesize = max($umf, $pms);
        }
        return $max_filesize;
    }


    private function getQuestionFileLink($filename)
    {
        if ($filename == "") {
            return "";
        }
        return "<a href='" . $this->createFilePathFromFragment($this->getQuestionFilePathFragment() . $filename) . "' download>" . $filename . "</a>";
    }
    
    public function getQuestionFilePathFragment($object_id = null, $question_id = null)
    {
        if (!$object_id)
            $object_id = $this->obj_id;
        if (!$question_id)
            $question_id = $this->id;

        return "assessment/" . $object_id . "/" . $question_id . "/files/";
    }
    
    public function createFilePathFromFragment($fragment, $absolute = false)
    {
        if ($fragment == "") {
            return "";
        }
        $path = "";
        if ($absolute) {
            $path = ILIAS_ABSOLUTE_PATH . "/";
        }
        return $path . ILIAS_WEB_DIR . "/" . CLIENT_ID . "/" .  $fragment;
    }
    
    
    /*
    * Returns the fragment path (e.g. last directories) for uploaded files from test participants
    */
    public function getParticipantSolutionFilePathFragment($test_id, $active_id)
    {
        return "assessment/tst_$test_id/$active_id/$this->id/files/";
    }
    
    private function getParticipantSolutionFilePath($test_id, $active_id, $filename, $absolute = false)
    {
        return $this->createFilePathFromFragment($this->getParticipantSolutionFilePathFragment($test_id, $active_id) . $filename, $absolute);
    }
    

    private function moveUploadedQuestionFile($temp_filename, $final_filename)
    {
        global $DIC;
        $upload = $DIC->upload();
        if (!$upload->hasBeenProcessed()) {
            $upload->process();
        }
        if (!$upload->hasUploads()) {
            throw new ilException($DIC->language()->txt("upload_error_file_not_found"));
        }
        $UploadResult = $upload->getResults()[$temp_filename];
        $ProcessingStatus = $UploadResult->getStatus();
        if ($ProcessingStatus->getCode() === ProcessingStatus::REJECTED) {
            throw new ilException($ProcessingStatus->getMessage());
        }
        $upload->moveOneFileTo($UploadResult, $this->getQuestionFilePathFragment(), Location::WEB, $final_filename, true);
    }
    
    private function deleteQuestionFile($filename)
    {
        global $DIC;
        if ($filename == "") {
            return false;
        }
        $WebDir = $DIC->filesystem()->web();
        $path = $this->getQuestionFilePathFragment() . $filename;
        if ($WebDir->has($path)) {
            $WebDir->delete($path);
            return true;
        }
        return false;
    }
    


    /**
     * Get the plugin object
     *
     * @return object The plugin object
     */
    public function getPlugin()
    {
        if ($this->plugin == null) {
            $this->plugin = ilPlugin::getPluginObject(IL_COMP_MODULE, 'TestQuestionPool', 'qst', 'assDataMiningQuestion');
        }
        return $this->plugin;
    }

    /**
     * Returns true, if the question is complete
     *
     * @return boolean True, if the question is complete for use, otherwise false
     */
    public function isComplete()
    {
        // Please add here your own check for question completeness
        // The parent function will always return false
        if (
            strlen($this->title) &&
            ($this->author) &&
            ($this->question) &&
            ($this->getMaximumPoints() >= 0) &&
            is_numeric($this->getMaximumPoints())
        ) {
            return true;
        }
        return false;
    }


    /**
     * Saves a question object to a database
     *
     * @param    string        $original_id
     * @access     public
     * @see assQuestion::saveToDb()
     */
    public function saveToDb($original_id = '')
    {
        global $ilDB;
        
        // save the basic data (implemented in parent)
        // a new question is created if the id is -1
        // afterwards the new id is set
        $this->saveQuestionDataToDb($original_id);
        
        //save question specific data
        $ilDB->manipulateF(
            "DELETE FROM " . $this->getAdditionalTableName() . " WHERE question_fi = %s",
            array("integer"),
            array($this->getId())
        );
        
        $ilDB->manipulateF(
            "INSERT INTO " . $this->getAdditionalTableName() . " (question_fi, training_file_name, test_feature_file_name, test_target_file_name, skip_first_line, evaluation_method, evaluation_average, evaluation_pos_label, evaluation_min, evaluation_max, evaluation_url, solution_file_suffix, maxsize) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            array("integer", "text", "text", "text", "integer", "text", "text", "text", "float", "float", "text", "text", "integer"),
            array(
                $this->getId(),
                $this->training_file_name,
                $this->test_feature_file_name,
                $this->test_target_file_name,
                (int)$this->skip_first_line,
                $this->evaluation_method,
                $this->evaluation_average,
                $this->evaluation_pos_label,
                $this->evaluation_min,
                $this->evaluation_max,
                $this->evaluation_url,
                $this->solution_file_suffix,
                (int)$this->maxsize
            )
        );
        
        // save stuff like suggested solutions
        // update the question time stamp and completion status
        parent::saveToDb($original_id);
    }

    /**
     * Loads a question object from a database
     * This has to be done here (assQuestion does not load the basic data)!
     *
     * @param integer $question_id A unique key which defines the question in the database
     * @see assQuestion::loadFromDb()
     */
    public function loadFromDb($question_id)
    {
        global $DIC;
        $ilDB = $DIC->database();
        
        $result = $ilDB->queryF(
            "SELECT qpl_questions.*, " . $this->getAdditionalTableName() . ".* FROM qpl_questions LEFT JOIN " . $this->getAdditionalTableName() . " ON " . $this->getAdditionalTableName() . ".question_fi = qpl_questions.question_id WHERE qpl_questions.question_id = %s",
            array("integer"),
            array($question_id)
        );

        if ($result->numRows() == 1) {
            $data = $ilDB->fetchAssoc($result);
            // load the basic question data
            $this->setId($question_id);
            $this->setTitle($data["title"]);
            $this->setComment($data["description"]);
            $this->setNrOfTries($data['nr_of_tries']);
            //$this->setSuggestedSolution($data["solution_hint"]);
            $this->setOriginalId($data["original_id"]);
            $this->setObjId($data["obj_fi"]);
            $this->setAuthor($data["author"]);
            $this->setOwner($data["owner"]);
            $this->setPoints($data["points"]);
            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));

            //load question specific data
            $this->setTrainingFileName($data["training_file_name"]);
            $this->setTestFeatureFileName($data["test_feature_file_name"]);
            $this->setTestTargetFileName($data["test_target_file_name"]);
            $this->setSkipFirstLine($data["skip_first_line"] == 1 ? true : false);
            $this->setEvaluationMethod($data["evaluation_method"]);
            $this->setEvaluationAverage($data["evaluation_average"]);
            $this->setEvaluationPosLabel($data["evaluation_pos_label"]);
            $this->setEvaluationMin($data["evaluation_min"]);
            $this->setEvaluationMax($data["evaluation_max"]);
            $this->setEvaluationUrl($data["evaluation_url"]);
            $this->setSolutionFileSuffix($data["solution_file_suffix"]);
            $this->setMaxSize($data["maxsize"]);
        }
        parent::loadFromDb($question_id);
    }
    

    /**
     * Duplicates a question
     * This is used for copying a question to a test
     *
     * @param bool           $for_test
     * @param string         $title
     * @param string         $author
     * @param string         $owner
     * @param integer|null    $testObjId
     *
     * @return void|integer Id of the clone or nothing.
     */
    public function duplicate($for_test = true, $title = '', $author = '', $owner = '', $testObjId = null)
    {

        if ($this->getId() <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }
        $this_id = $this->getId();
        $thisObjId = $this->getObjId();

        // make a real clone to keep the actual object unchanged
        $clone = clone $this;
                            
        $original_id = assQuestion::_getOriginalId($this->getId());
        $clone->setId(-1);

        if ((int) $testObjId > 0) {
            $clone->setObjId($testObjId);
        }

        if (!empty($title)) {
            $clone->setTitle($title);
        }
        if (!empty($author)) {
            $clone->setAuthor($author);
        }
        if (!empty($owner)) {
            $clone->setOwner($owner);
        }
        
        if ($for_test) {
            $clone->saveToDb($original_id);
        } else {
            $clone->saveToDb();
        }

        // copy question page content
        $clone->copyPageOfQuestion($this->getId());
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($this->getId());

        $clone->copyDir($this_id, $thisObjId);
        // call the event handler for duplication
        $clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

        return $clone->getId();
    }


    /**
     * Copies a question
     * This is used when a question is copied on a question pool
     *
     * @param integer    $target_questionpool_id
     * @param string    $title
     *
     * @return void|integer Id of the clone or nothing.
     */
    public function copyObject($target_questionpool_id, $title = '')
    {

        if ($this->getId() <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }

        // make a real clone to keep the object unchanged
        $clone = clone $this;
                
        $original_id = assQuestion::_getOriginalId($this->getId());
        $source_questionpool_id = $this->getObjId();
        $clone->setId(-1);
        $clone->setObjId($target_questionpool_id);
        if (!empty($title)) {
            $clone->setTitle($title);
        }
                
        // save the clone data
        $clone->saveToDb();

        // copy question page content
        $clone->copyPageOfQuestion($original_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($original_id);
        $clone->copyDir($original_id, $source_questionpool_id);
        // call the event handler for copy
        $clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

        return $clone->getId();
    }

    /**
     * Create a new original question in a question pool for a test question
     * @param int $targetParentId            id of the target question pool
     * @param string $targetQuestionTitle
     * @return int|void
     */
    public function createNewOriginalFromThisDuplicate($targetParentId, $targetQuestionTitle = '')
    {
        if ($this->id <= 0) {
            // The question has not been saved. It cannot be duplicated
            return;
        }

        $sourceQuestionId = $this->id;
        $sourceParentId = $this->getObjId();

        // make a real clone to keep the object unchanged
        $clone = clone $this;
        $clone->setId(-1);

        $clone->setObjId($targetParentId);

        if (!empty($targetQuestionTitle)) {
            $clone->setTitle($targetQuestionTitle);
        }

        $clone->saveToDb();
        // copy question page content
        $clone->copyPageOfQuestion($sourceQuestionId);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($sourceQuestionId);
        $clone->copyDir($sourceQuestionId, $sourceParentId);

        $clone->onCopy($sourceParentId, $sourceQuestionId, $clone->getObjId(), $clone->getId());

        return $clone->getId();
    }


    private function copyDir($question_id, $objectId = null)
    {
        //($thisObjId = $this->getObjId();)
        global $DIC;
        $WebDir = $DIC["filesystem"]->web();

        $filePathOriginal = $this->getQuestionFilePathFragment($object_id, $question_id);
        $filePath = $this->getQuestionFilePathFragment($object_id, $this->id);

        $filePathOriginal = str_replace($this->obj_id . "/", $objectId . "/", $filePathOriginal);
        $WebDir->copyDir($filePathOriginal, $filePath);
    }


    /**
     * Synchronize a question with its original
     * You need to extend this function if a question has additional data that needs to be synchronized
     *
     * @access public
     */
    public function syncWithOriginal()
    {
        parent::syncWithOriginal();
    }



    /**
     * Get a stored solution for a user and test pass
     * This is a wrapper to provide the same structure as getSolutionSubmit()
     *
     * @param int     $active_id        active_id of hte user
     * @param int    $pass            number of the test pass
     * @param bool    $authorized        get the authorized solution
     *
     * @return    array    ('value1' => string|null, 'value2' => float|null)
     */
    public function getSolutionStored($active_id, $pass, $authorized = null)
    {
        global $DIC;
        $WebDir = $DIC["filesystem"]->web();

        // This provides an array with records from tst_solution
        // The example question should only store one record per answer
        // Other question types may use multiple records with value1/value2 in a key/value style
        if (isset($authorized)) {
            // this provides either the authorized or intermediate solution
            $solutions = $this->getSolutionValues($active_id, $pass, $authorized);
        } else {
            // this provides the solution preferring the intermediate
            // or the solution from the previous pass
            $solutions = $this->getTestOutputSolutions($active_id, $pass);
        }

        if (empty($solutions)) {
            // no solution stored yet
            $value1 = null;
            $value2 = null;
            $date = null;
            $size = null;
        } else {
            $solution = end($solutions);
            $value1 = $solution['value1']; // value1 is the full file path of the uploaded file
            $value2 = $solution['value2'];
            $date = null;
            $size = null;
            if (($value1 != "") && ($WebDir->has($value1))) {
                $date   = date('d.m.y - H:i', $solution['tstamp']);
                $size   = $WebDir->getSize($value1, DataSize::Byte)->getSize();
            }
        }

        return array(
            'value1' => empty($value1) ? null : (string) $value1,
            'value2' => empty($value2) ? null : (string) $value2,
            'date' => empty($date) ? null : $date,
            'size' => empty($date) ? null : $size,
        );
    }


    /**
    * Returns the maximum points, a learner can reach answering the question
    *
    * @see $points
    */
    public function getMaximumPoints()
    {
        return $this->getPoints();
    }



    /**
     * Returns the points, a learner has reached answering the question
     * The points are calculated from the given answers.
     *
     * @param int $active_id
     * @param integer $pass The Id of the test pass
     * @param bool $authorizedSolution
     * @param boolean $returndetails (deprecated !!)
     * @return int
     *
     * @throws ilTestException
     */
    public function calculateReachedPoints($active_id, $pass = null, $authorizedSolution = true, $returndetails = false)
    {
        if ($returndetails) {
            throw new ilTestException('return details not implemented for ' . __METHOD__);
        }
        if (is_null($pass)) {
            $pass = $this->getSolutionMaxPass($active_id);
        }
        
        // get the answers of the learner from the tst_solution table
        // the data is saved by saveWorkingData() in this class
        $solution = $this->getSolutionStored($active_id, $pass, $authorizedSolution);
        
        try {
            $systemFilePath = $this->createFilePathFromFragment($solution['value1'], true);
            $pointDescription = calculateEvalMeasure::calculateObject($this, $systemFilePath);
            return $pointDescription->getPoints();
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            return 0;
        }
    }
    

    /**
     * Saves the learners input of the question to the database.
     *
     * @param integer $active_id     Active id of the user
     * @param integer $pass          Test pass of the user
     * @param boolean $authorized    Auto saved answers are non authorized answers (e.g. if just the upload btn is pressed).
     *                               A solution is authorized if the user presses the btn for the next question (explicit feedback of participant).
     * @return boolean $status
     */
    public function saveWorkingData($active_id, $pass = null, $authorized = true)
    {
        $pass = $this->ensureCurrentTestPass($active_id, $pass);
        $test_id = $this->lookupTestId($active_id);
        
        $entered_values = false;
        //file_put_contents('myfile.log', "active_id: $active_id , pass: $pass, authorized: $authorized \n", FILE_APPEND);
        
        $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(function () use (&$entered_values, $test_id, $active_id, $pass, $authorized) {
            global $DIC;
            $WebDir = $DIC->filesystem()->web();

	    // after toggling to "autorized_solution", we delete the former authorized_solution - 
            // so we have to be shure, that an IntermediateSolution exists
            if ($authorized == false)
            {
                //file_put_contents('myfile.log', "Create Dummy\n", FILE_APPEND);
                $this->forceExistingIntermediateSolution($active_id, $pass, true);
            }
            
            if ($this->isFileUploadAvailable()) {
                $upload = $DIC->upload();
                
                if (!empty($this->getSolutionFileSuffixArray())) {
                    $upload->register(new WhitelistExtensionPreProcessor($this->getSolutionFileSuffixArray()));
                }
                $upload->register(new FileSizePreProcessor($this->getMaxSize()));
                if (IL_VIRUS_SCANNER != "None") {
                    require_once("./Services/VirusScanner/classes/class.ilVirusScannerFactory.php");
                    $upload->register(new VirusScannerPreProcessor(ilVirusScannerFactory::_getInstance()));
                }
                
                $upload->process();
                if (!$upload->hasUploads()) {
                    ilUtil::sendFailure($DIC->language()->txt("upload_error_file_not_found"), true);
                    return;
                }
                
                $UploadResult = $upload->getResults()[$_FILES["upload"]["tmp_name"]];
                $ProcessingStatus = $UploadResult->getStatus();
                if ($ProcessingStatus->getCode() === ProcessingStatus::REJECTED) {
                    ilUtil::sendFailure($ProcessingStatus->getMessage(), true);
                    return;
                }
                
                //check system file:
                try {
                    $pointDescription = calculateEvalMeasure::calculateObject($this, $_FILES["upload"]["tmp_name"]);
                } catch (Exception $e) {
                    ilUtil::sendFailure($e->getMessage(), true);
                    return;
                }
                
                //move file
                $timestamp = time();
                $filename_arr = pathinfo($_FILES["upload"]["name"]);
                $extension = $filename_arr["extension"];
                $newfile = "file_" . $active_id . "_" . $pass . "_" . $timestamp . "." . $extension;
                
                include_once 'Services/Utilities/classes/class.ilFileUtils.php';
                $dispoFilename = ilFileUtils::getValidFilename($_FILES['upload']['name']);
                $newfile = ilFileUtils::getValidFilename($newfile);
                
                $upload->moveOneFileTo($UploadResult, "/" . $this->getParticipantSolutionFilePathFragment($test_id, $active_id), Location::WEB, $newfile, true);
                //full path is stored in database because if path changes, old pathes are stored in db.
                $fullPath = $this->getParticipantSolutionFilePathFragment($test_id, $active_id) . $newfile;
                
                $this->removeCurrentSolution($active_id, $pass, false);
                $this->saveCurrentSolution($active_id, $pass, $fullPath, $dispoFilename, false, $timestamp);
                //$this->saveCurrentSolution($active_id, $pass, "hello", "world", false, $timestamp);
                //$this->saveCurrentSolution($active_id, $pass, "filename", "test", false, $timestamp);
                
                $entered_values = true;
            }
            
	    if ($authorized == true && $this->myIntermediateSolutionExists($active_id, $pass))
            {
                //file_put_contents('myfile.log', "Delete Dummy\n", FILE_APPEND);
                // remove the dummy record of the intermediate solution
                $this->deleteDummySolutionRecord($active_id, $pass);
                // delete the authorized solution and make the intermediate solution authorized (keeping timestamps)
                $this->removeCurrentSolution($active_id, $pass, true);
                $this->updateCurrentSolutionsAuthorization($active_id, $pass, true, true);
            }
            $this->deleteUnusedFiles($test_id, $active_id, $pass);
        });

        // Log whether the user entered values
        if (ilObjAssessmentFolder::_enabledAssessmentLogging()) {
            assQuestion::logAction(
                $this->lng->txtlng(
                    'assessment',
                    $entered_values ? 'log_user_entered_values' : 'log_user_not_entered_values',
                    ilObjAssessmentFolder::_getLogLanguage()
                ),
                $active_id,
                $this->getId()
            );
        }
        return true;
    }

    public function myIntermediateSolutionExists($active_id, $pass)
    {
        $solutionAvailability = $this->lookupForExistingSolutions($active_id, $pass);
        return (bool)$solutionAvailability['intermediate'];
    }


    
    /**
     * Delete all files that are neither used in an authorized or intermediate solution
     * @param int   $test_id
     * @param int   $active_id
     * @param int   $pass
     */
    protected function deleteUnusedFiles($test_id, $active_id, $pass)
    {
        // read all solutions (authorized and intermediate) from all steps
        $step = $this->getStep();
        $this->setStep(null);
        $solutions = array_merge(
            $this->getSolutionValues($active_id, $pass, true),
            $this->getSolutionValues($active_id, $pass, false)
        );
        $this->setStep($step);

        // get the used files from these solutions
        $used_files = array();
        foreach ($solutions as $solution) {
            $filename_arr = pathinfo($solution['value1']);
            $used_files[] = $filename_arr['basename'];
        }

        // read the existing files for user and pass
        // delete all files that are not used in the solutions
        $uploadPath = $this->getParticipantSolutionFilePath($test_id, $active_id, "", true);
        if (is_dir($uploadPath) && is_readable($uploadPath)) {
            $iter = new \RegexIterator(new \DirectoryIterator($uploadPath), '/^file_' . $active_id . '_' . $pass . '_(.*)/');
            foreach ($iter as $file) {
                if ($file->isFile() && !in_array($file->getFilename(), $used_files)) {
                    unlink($file->getPathname());
                }
            }
        }
    }
    
    /**
     * Checks if a file is currently uploaded (checks for $_FILES['upload']).
     * @return bool is file upload is available
     */
    private function isFileUploadAvailable()
    {
        if (!isset($_FILES['upload'])) {
            return false;
        }
        if (!isset($_FILES['upload']['tmp_name'])) {
            return false;
        }
        return strlen($_FILES['upload']['tmp_name']) > 0;
    }


    /**
     * Reworks the allready saved working data if neccessary
     * @param integer $active_id
     * @param integer $pass
     * @param boolean $obligationsAnswered
     * @param boolean $authorized
     */
    public function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
    {
        // normally nothing needs to be reworked
    }


    /**
     * Creates an Excel worksheet for the detailed cumulated results of this question
     *
     * @param object $worksheet    Reference to the parent excel worksheet
     * @param int $startrow     Startrow of the output in the excel worksheet
     * @param int $active_id    Active id of the participant
     * @param int $pass         Test pass
     *
     * @return int
     */
    public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
    {
        parent::setExportDetailsXLS($worksheet, $startrow, $active_id, $pass);

        $solution = $this->getSolutionStored($active_id, $pass, true);
        $value1 = isset($solution['value1']) ? $solution['value1'] : '';
        $value2 = isset($solution['value2']) ? $solution['value2'] : '';

        $row = $startrow + 1;
        $worksheet->setCell($row, 0, $this->plugin->txt('label_value1'));
        $worksheet->setBold($worksheet->getColumnCoord(0) . $row);
        $worksheet->setCell($row, 1, $value1);
        $row++;

        $worksheet->setCell($row, 0, $this->plugin->txt('label_value2'));
        $worksheet->setBold($worksheet->getColumnCoord(0) . $row);
        $worksheet->setCell($row, 1, $value2);
        $row++;

        return $row + 1;
    }

    /**
     * Creates a question from a QTI file
     *
     * Receives parameters from a QTI parser and creates a valid ILIAS question object
     *
     * @param object $item The QTI item object
     * @param integer $questionpool_id The id of the parent questionpool
     * @param integer $tst_id The id of the parent test if the question is part of a test
     * @param object $tst_object A reference to the parent test object
     * @param integer $question_counter A reference to a question counter to count the questions of an imported question pool
     * @param array $import_mapping An array containing references to included ILIAS objects
     * @access public
     */
    public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
    {
        $this->getPlugin()->includeClass("import/qti12/class.assDataMiningQuestionImport.php");
        $import = new assDataMiningQuestionImport($this);
        $import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
    }

    /**
     * Returns a QTI xml representation of the question and sets the internal
     * domxml variable with the DOM XML representation of the QTI xml representation
     *
     * @return string The QTI xml representation of the question
     * @access public
     */
    public function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
    {
        $this->getPlugin()->includeClass("export/qti12/class.assDataMiningQuestionExport.php");
        $export = new assDataMiningQuestionExport($this);
        return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
    }
}
