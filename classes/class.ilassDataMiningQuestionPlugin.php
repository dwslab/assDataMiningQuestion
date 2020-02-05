<?php

include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";
    
/**
* Question plugin Example
*
* @author Sven Hertling <sven@informatik.uni-mannheim.de>
* @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
* @author Nicolas Heist <nico@informatik.uni-mannheim.de>
* @version $Id$
* @ingroup ModulesTestQuestionPool
*/
class ilassDataMiningQuestionPlugin extends ilQuestionsPlugin
{
    final public function getPluginName()
    {
        return "assDataMiningQuestion";
    }
        
    final public function getQuestionType()
    {
        return "assDataMiningQuestion";
    }
        
    final public function getQuestionTypeTranslation()
    {
        return $this->txt($this->getQuestionType());
    }
}
