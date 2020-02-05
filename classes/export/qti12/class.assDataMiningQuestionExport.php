<?php

include_once "./Modules/TestQuestionPool/classes/export/qti12/class.assQuestionExport.php";

/**
 * Export of Data Mining question.
 *
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 * @version    $Id:  $
 */
class assDataMiningQuestionExport extends assQuestionExport
{
    /**
    * Returns a QTI xml representation of the question
    *
    * @return string The QTI xml representation of the question
    * @access public
    */
    function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
    {
        global $ilias;

        include_once("./Services/Xml/classes/class.ilXmlWriter.php");
        $a_xml_writer = new ilXmlWriter;
        // set xml header
        $a_xml_writer->xmlHeader();
        $a_xml_writer->xmlStartTag("questestinterop");
        $attrs = array(
            "ident" => "il_".IL_INST_ID."_qst_".$this->object->getId(),
            "title" => $this->object->getTitle(),
            "maxattempts" => $this->object->getNrOfTries()
        );
        $a_xml_writer->xmlStartTag("item", $attrs);
        // add question description
        $a_xml_writer->xmlElement("qticomment", NULL, $this->object->getComment());
        // add estimated working time
        $workingtime = $this->object->getEstimatedWorkingTime();
        $duration = sprintf("P0Y0M0DT%dH%dM%dS", $workingtime["h"], $workingtime["m"], $workingtime["s"]);
        $a_xml_writer->xmlElement("duration", NULL, $duration);
        // add ILIAS specific metadata
        $a_xml_writer->xmlStartTag("itemmetadata");
        $a_xml_writer->xmlStartTag("qtimetadata");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "ILIAS_VERSION");
        $a_xml_writer->xmlElement("fieldentry", NULL, $ilias->getSetting("ilias_version"));
        $a_xml_writer->xmlEndTag("qtimetadatafield");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "QUESTIONTYPE");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getQuestionType());
        $a_xml_writer->xmlEndTag("qtimetadatafield");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "AUTHOR");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getAuthor());
        $a_xml_writer->xmlEndTag("qtimetadatafield");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "POINTS");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getPoints());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // my custom stuff
/*
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVAL_METHOD");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->EvaluationMethod->getEvaluationMethod());
        $a_xml_writer->xmlEndTag("qtimetadatafield");
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVAL_URL");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->EvaluationMethod->getEvaluationURL());
        $a_xml_writer->xmlEndTag("qtimetadatafield");
*/
        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "SKIP_FIRST_LINE");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getSkipFirstLine()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_METHOD");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationMethod()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_AVERAGE");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationAverage()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_POS_LABEL");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationPosLabel()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_MIN");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationMin()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_MAX");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationMax()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "EVALUATION_URL");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getEvaluationUrl()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "MAXSIZE");
        $a_xml_writer->xmlElement("fieldentry", NULL, $this->object->getMaxSize());
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        $a_xml_writer->xmlStartTag("qtimetadatafield");
        $a_xml_writer->xmlElement("fieldlabel", NULL, "ALLOWED_SUFFIX");
        $a_xml_writer->xmlElement("fieldentry", NULL, base64_encode($this->object->getSolutionFileSuffix()));
        $a_xml_writer->xmlEndTag("qtimetadatafield");

        // additional content editing information
        $this->addAdditionalContentEditingModeInformation($a_xml_writer);
        $this->addGeneralMetadata($a_xml_writer);

        $a_xml_writer->xmlEndTag("qtimetadata");
        $a_xml_writer->xmlEndTag("itemmetadata");

        // PART I: qti presentation
        $attrs = array(
            "label" => $this->object->getTitle()
        );

        $a_xml_writer->xmlStartTag("presentation", $attrs);
        // add flow to presentation
        $a_xml_writer->xmlStartTag("flow");

        // add material with question text to presentation
        $this->object->addQTIMaterial($a_xml_writer, $this->object->getQuestion());

        $a_xml_writer->xmlStartTag("response_lid", $attrs);
        $a_xml_writer->xmlStartTag("render_fib");


        global $DIC;
        $WebDir = $DIC["filesystem"]->web();
        //$dataFolder = $this->object->getUploadPathQuestion();
        $dataFolder = $this->object->getQuestionFilePathFragment();

	$mat_index = 0;

        foreach (array("training_file", "test_file_features", "test_file_class") as $type_of_file) {
            switch ($type_of_file)
            {
                case "training_file":
                    $filename = $this->object->getTrainingFileName();
                    break;
                case "test_file_features":
                    $filename = $this->object->getTestFeatureFileName();
                    break;
                case "test_file_class":
                    $filename = $this->object->getTestTargetFileName();
                    break;
                default:
                    break;
            }

            if (($filename == "") || (!$WebDir->has($dataFolder . $filename )) ) 
                continue;

            $filedata = $WebDir->read($dataFolder . $filename);

            $attrs = array(
                "ident" => $mat_index
            );
            $a_xml_writer->xmlStartTag("response_label", $attrs);


            $a_xml_writer->xmlStartTag("material");
            $attrs = array(
                "label"    => $type_of_file,
	        "uri"      => $filename,
                "embedded" => "base64"
            );
            $a_xml_writer->xmlElement("mattext", $attrs, base64_encode($filedata));
            $a_xml_writer->xmlEndTag("material");

            $a_xml_writer->xmlEndTag("response_label");
	    $mat_index ++;
        } 

        $a_xml_writer->xmlEndTag("render_fib");
        $a_xml_writer->xmlEndTag("response_lid");
        $a_xml_writer->xmlEndTag("flow");
        $a_xml_writer->xmlEndTag("presentation");


        // PART III: qti itemfeedback
        $feedback_allcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
            $this->object->getId(), true
        );

        $feedback_onenotcorrect = $this->object->feedbackOBJ->getGenericFeedbackExportPresentation(
            $this->object->getId(), false
        );

        $attrs = array(
            "ident" => "Correct",
            "view" => "All"
        );
        $a_xml_writer->xmlStartTag("itemfeedback", $attrs);
        // qti flow_mat
        $a_xml_writer->xmlStartTag("flow_mat");
        $a_xml_writer->xmlStartTag("material");
        $a_xml_writer->xmlElement("mattext");
        $a_xml_writer->xmlEndTag("material");
        $a_xml_writer->xmlEndTag("flow_mat");
        $a_xml_writer->xmlEndTag("itemfeedback");
        if (strlen($feedback_allcorrect))
        {
            $attrs = array(
                "ident" => "response_allcorrect",
                "view" => "All"
            );
            $a_xml_writer->xmlStartTag("itemfeedback", $attrs);
            // qti flow_mat
            $a_xml_writer->xmlStartTag("flow_mat");
            $this->object->addQTIMaterial($a_xml_writer, $feedback_allcorrect);
            $a_xml_writer->xmlEndTag("flow_mat");
            $a_xml_writer->xmlEndTag("itemfeedback");
        }
        if (strlen($feedback_onenotcorrect))
        {
            $attrs = array(
                "ident" => "response_onenotcorrect",
                "view" => "All"
            );
            $a_xml_writer->xmlStartTag("itemfeedback", $attrs);
            // qti flow_mat
            $a_xml_writer->xmlStartTag("flow_mat");
            $this->object->addQTIMaterial($a_xml_writer, $feedback_onenotcorrect);
            $a_xml_writer->xmlEndTag("flow_mat");
            $a_xml_writer->xmlEndTag("itemfeedback");
        }

        $a_xml_writer->xmlEndTag("item");
        $a_xml_writer->xmlEndTag("questestinterop");

        $xml = $a_xml_writer->xmlDumpMem(FALSE);
        if (!$a_include_header)
        {
            $pos = strpos($xml, "?>");
            $xml = substr($xml, $pos + 2);
        }
        return $xml;
    }
}

?>
