<#1>
<?php

/**
 * Data Mining Question plugin - update script.
 *
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 * @version $Id$
 */

//Create the new question type
$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assDataMiningQuestion'));
if ($res->numRows() == 0) {
    $res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
    $data = $ilDB->fetchAssoc($res);
    $max = $data["maxid"] + 1;

    $affectedRows = $ilDB->manipulateF(
        "INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)",
        array("integer", "text", "integer"),
        array($max, 'assDataMiningQuestion', 1)
    );
}

//add table for questions
$fields = array(
    'question_fi' => array(
        'type'   => 'integer',
        'length' => 4
    ),
    'training_file_name' => array(
        'type'   => 'text',
        'length' => 512
    ),
    'test_feature_file_name' => array(
        'type'   => 'text',
        'length' => 512
    ),
    'test_target_file_name' => array(
        'type'   => 'text',
        'length' => 512
    ),
    'skip_first_line' => array(
        'type'   => 'integer',
        'length' => 4
    ),
    'evaluation_method' => array(
        'type'   => 'text',
        'length' => 128
    ),
    'evaluation_average' => array(
        'type'   => 'text',
        'length' => 128
    ),
    'evaluation_pos_label' => array(
        'type'   => 'text',
        'length' => 128
    ),
    'evaluation_min' => array(
        'type'   => 'float'
    ),
    'evaluation_max' => array(
        'type'   => 'float'
    ),
    'evaluation_url' => array(
        'type'   => 'text',
        'length' => 512
    ),
    'solution_file_suffix' => array(
        'type'   => 'text',
        'length' => 512
    ),
    'maxsize' => array(
        'type'   => 'integer',
        'length' => '4'
    )
);

if ($ilDB->tableExists("qpl_qst_datamining")) {
    $ilDB->dropTable("qpl_qst_datamining");
}
$ilDB->createTable("qpl_qst_datamining", $fields);
$ilDB->addPrimaryKey("qpl_qst_datamining", array("question_fi"));

?>
