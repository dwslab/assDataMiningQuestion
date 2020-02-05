<?php

require_once 'class.PointsAndDescription.php';

/**
 * Class calculateEvalMeasure
 *
 * Calculates the evaluation measures such as precision, recall etc.
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 *
 */
class calculateEvalMeasure
{
    /*******************
    * PUBLIC API
    ********************/
    
    /**
    * All available classification methods as array
    * with variable name as key and text as value.
    */
    public static $CLASSIFICATION_METHODS = array (
        "precision"  => "eval_precision",
        "recall" => "eval_recall",
        "fmeasure"   => "eval_fmeasure"
    );
    /**
    * All available regression methods as array
    * with variable name as key and text as value.
    */
    public static $REGRESSION_METHODS = array (
        "max_error"  => "eval_max_error",
        "mean_absolute_error" => "eval_mean_absolute_error",
        "mean_squared_error"   => "eval_mean_squared_error",
        "root_mean_squared_error"   => "eval_root_mean_squared_error"
    );
    
    public static function calculateObject($obj, $systemFilePath = null)
    {
        return self::calculate(
            $obj->getEvaluationMethod(),
            $obj->getSkipFirstLine(),
            $obj->getPoints(),
            $obj->getEvaluationAverage(),
            $obj->getEvaluationPosLabel(),
            $obj->getEvaluationMin(),
            $obj->getEvaluationMax(),
            $obj->getEvaluationUrl(),
            $obj->getTestTargetFilePath(true),
            $systemFilePath
        );
    }
    
    public static function calculate($method, $removeHeaderOfGoldCsv, $maxPoints, $average, $posLabel, $min, $max, $url, $goldFilePath, $systemFilePath = null)
    {
        $pointDescriptionObject = null;
        switch ($method) {
            case "accuracy":
                $pointDescriptionObject = self::accuracy($removeHeaderOfGoldCsv, $goldFilePath, $systemFilePath);
                break;
            case "precision":
            case "recall":
            case "fmeasure":
                $pointDescriptionObject = self::computeClassification($method, $removeHeaderOfGoldCsv, $average, $posLabel, $goldFilePath, $systemFilePath);
                break;
            case "max_error":
            case "mean_absolute_error":
            case "mean_squared_error":
            case "root_mean_squared_error":
                $pointDescriptionObject = self::computeRegression($method, $removeHeaderOfGoldCsv, $min, $max, $goldFilePath, $systemFilePath);
                break;
            case "custom":
                $pointDescriptionObject = self::callEndpoint($url, $removeHeaderOfGoldCsv, $goldFilePath, $systemFilePath);
                break;
            default:
                throw new Exception("Evaluation method is not implemented!");
        }
        //check point range
        $points = $pointDescriptionObject->getPoints();
        if ($points < 0.0 || $points > 1.0) {
            throw new Exception("Computed measure is greater one or smaller zero. Value: " . $points);
        }
        $points = $maxPoints * $points; //scale to max points
        $points = round($points, 4); //round points to 4 decimals
        
        //check description length and encode to be displayed in html
        $description = $pointDescriptionObject->getDescription();
        if (strlen($description) > 1000) {
            $description = substr($description, 0, 1000) . "...";
        }
        $description = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        return new PointsAndDescription($points, $description);
    }

    /*******************************************************
    * COMPUTE CLASSIFICATION
    ********************************************************/
    
    private static function computeClassification($method, $removeHeader, $average, $posLabel, $goldFilePath, $systemFilePath = null)
    {
        list($actualLabels, $predictedLabels, $description) = self::checkClassification($removeHeader, $average, $posLabel, $goldFilePath, $systemFilePath);
        if (isset($description)) {
            return $description;
        }
        
        $labels = array_values(array_unique(array_merge($actualLabels, $predictedLabels)));
        sort($labels);
        $truePositive = $falsePositive = $falseNegative = $support = (array) array_combine($labels, array_fill(0, count($labels), 0));
        foreach ($actualLabels as $index => $actual) {
            $predicted = $predictedLabels[$index];
            ++$support[$actual];
            if ($actual === $predicted) {
                ++$truePositive[$actual];
            } else {
                ++$falsePositive[$predicted];
                ++$falseNegative[$actual];
            }
        }
        
        foreach ($truePositive as $label => $tp) {
            $precision[$label] = self::safeDivision($tp, $tp, $falsePositive[$label]);
            $recall[$label] = self::safeDivision($tp, $tp, $falseNegative[$label]);
            $f1score[$label] = self::safeDivision(2.0 * ($precision[$label] * $recall[$label]), $precision[$label], $recall[$label]);
        }
        
        $averagedPrecision = 0.0;
        $averagedRecall = 0.0;
        $averagedF1 = 0.0;
        switch ($average) {
            case "binary":
                if (!array_key_exists($posLabel, $truePositive)) {
                    throw new Exception("posLabel is not contained in confusion matrix");
                }
                $averagedPrecision = $precision[$posLabel];
                $averagedRecall = $recall[$posLabel];
                $averagedF1 = $f1score[$posLabel];
                break;
            
            case "micro":
                $truePositiveSum = (int) array_sum($truePositive);
                $falsePositiveSum = (int) array_sum($falsePositive);
                $falseNegativeSum = (int) array_sum($falseNegative);
                $averagedPrecision = self::safeDivision($truePositiveSum, $truePositiveSum, $falsePositiveSum);
                $averagedRecall = self::safeDivision($truePositiveSum, $truePositiveSum, $falseNegativeSum);
                $averagedF1 = self::safeDivision(2.0 * ($averagedPrecision * $averagedRecall), $averagedPrecision, $averagedRecall);
                break;
                
            case "macro":
                $averagedPrecision = (count($precision) == 0 ? 0 : (array_sum($precision) / count($precision)));
                $averagedRecall = (count($recall) == 0 ? 0 : (array_sum($recall) / count($recall)));
                $averagedF1 = (count($f1score) == 0 ? 0 : (array_sum($f1score) / count($f1score)));
                break;
            
            case "weighted":
                $averagedPrecision = (count($precision) == 0 ? 0 : self::weightedSum($precision, $support));
                $averagedRecall = (count($recall) == 0 ? 0 : self::weightedSum($recall, $support));
                $averagedF1 = (count($f1score) == 0 ? 0 : self::weightedSum($f1score, $support));
                break;
                
            default:
                throw new Exception("Average method is not implemented!");
        }
        
        switch ($method) {
            case "precision":
                return new PointsAndDescription($averagedPrecision, "The computed " . $average . " precision is " . round($averagedPrecision, 4));
            case "recall":
                return new PointsAndDescription($averagedRecall, "The computed " . $average . " recall is " . round($averagedRecall, 4));
            case "fmeasure":
                return new PointsAndDescription($averagedF1, "The computed " . $average . " f measure is " . round($averagedF1, 4));
            default:
                throw new Exception("Evaluation method is not implemented!");
        }
    }
    
    private static function accuracy($removeHeader, $goldFilePath, $systemFilePath = null)
    {
        list($actualLabels, $predictedLabels, $description) =  self::checkClassification($removeHeader, null, null, $goldFilePath, $systemFilePath);
        if (isset($description)) {
            return $description;
        }
        
        $truePositive = 0;
        foreach ($actualLabels as $index => $actual) {
            $predicted = $predictedLabels[$index];
            if ($actual === $predicted) {
                $truePositive++;
            }
        }
        $accuracy = $truePositive / count($predictedLabels);
        return new PointsAndDescription($accuracy, "The computed accuracy is " . round($accuracy, 4) . ". You had " . $truePositive . " true positives out of " . count($actualLabels) . " examples.");
    }
    
    private static function safeDivision(float $numerator, float $denominatorOne, float $denominatorTwo)
    {
        if (($denominatorOne + $denominatorTwo) > 0.0) {
            return $numerator / ($denominatorOne + $denominatorTwo);
        } else {
            return 0.0;
        }
    }
    
    private static function weightedSum(array $values, array $weights)
    {
        $sum = 0;
        foreach ($values as $i => $value) {
            $sum += $value * $weights[$i];
        }
        return $sum / array_sum($weights);
    }
    
    private static function checkClassification($removeHeader, $average, $posLabel, $goldFilePath, $systemFilePath = null)
    {
        $gold = self::parseCsv($goldFilePath, $removeHeader);
        if (count($gold) <= 1) {
            throw new Exception("Gold Standard contains only zero or one example. Something went wrong. Check Gold Standard file.");
        }
        $distinctGold = array_unique($gold);
        if (count($gold) == count($distinctGold)) {
            throw new Exception("Each example of the gold standard has a different class - this is propably not intended and is more a parsing error.");
        }
        if ($average == "binary") {
            if (!in_array($posLabel, $distinctGold)) {
                throw new Exception("Positive label of binary averaging is not contained in gold standard.");
            }
        }
        
        if (!isset($systemFilePath)) {
            $desc = "Parsed " . count($gold) . " examples with " . count($distinctGold) . " distinct values";
            if(count($distinctGold) < 6){
                $desc .=  " which are: \"" . implode(",", $distinctGold) . "\"";
            }
            return array(null, null, new PointsAndDescription(0.0, $desc));
        }
        //Check system
        $system = self::parseCsv($systemFilePath, false);
        if (count($system) <= 1) {
            throw new Exception("CSV contains only zero or one example. Something went wrong. Check csv file.");
        }
        $checkedSystem = [];
        foreach ($system as $i => $value) {
            if (!in_array($value, $distinctGold)) { //not in gold values
                if ($i == 0) {//if header
                    continue; // silently skip header
                } else {
                    throw new Exception("Error when parsing csv: \"" . $value . "\" is not one of \"" . implode(",", $distinctGold) . "\"");
                }
            }
            $checkedSystem[] = $value;
        }
        if (count($gold) != count($checkedSystem)) {
            throw new Exception("Number of values of system does not match the gold standard. System has " . count($checkedSystem) . " valid example(s) whereas gold standard has " . count($gold) . " examples.");
        }
        return array($gold, $checkedSystem, null);
    }
    
    /*******************************
    * COMPUTE REGRESSION
    ********************************/
    
    private static function computeRegression($method, $removeHeader, $min, $max, $goldFilePath, $systemFilePath = null)
    {
        list($actualLabels, $predictedLabels, $description) = self::checkRegression($removeHeader, $goldFilePath, $systemFilePath);
        if (isset($description)) {
            return $description;
        }
        $error = 0.0;
        switch ($method) {
            case "max_error":
                foreach (array_combine($actual, $predicted) as $gold => $system) {
                    if ($error < abs($gold - $system)) {
                        $error = abs($gold - $system);
                    }
                }
                break;
            case "mean_absolute_error":
                $absolute_error = 0.0;
                foreach (array_combine($actual, $predicted) as $gold => $system) {
                    $absolute_error += abs($gold - $system);
                }
                $error = $absolute_error / count($predicted);
                break;
            case "mean_squared_error":
                $absolute_error = 0.0;
                foreach (array_combine($actual, $predicted) as $gold => $system) {
                    $absolute_error += pow($gold - $system, 2);
                }
                $error = $absolute_error / count($predicted);
                break;
            case "root_mean_squared_error":
                $absolute_error = 0.0;
                foreach (array_combine($actual, $predicted) as $gold => $system) {
                    $absolute_error += pow($gold - $system, 2);
                }
                $error = sqrt($absolute_error / count($predicted));
                break;
            default:
                throw new Exception("Evaluation method is not implemented!");
        }
        $normalized = self::normalize($error, $min, $max);

        return new PointsAndDescription($normalized, "The calculated " . $method . " is " . $error);
    }
    
    /**
     * Check if all values are correctly set.
     * @param Boolean   $removeHeader  if the header of the gold standard should be parsed.
     * @param Float   $goldFilePath  min value
     * @param Float   $max  maxvalue
     * @return normalized value
     */
    private static function checkRegression($removeHeader, $goldFilePath, $systemFilePath = null)
    {
        $goldText = self::parseCsv($goldFilePath, $removeHeader);
        if (count($goldText) <= 1) {
            throw new Exception("Gold Standard contains only zero or one example. Something went wrong. Check Gold Standard file.");
        }
        $checkedGold = [];
        foreach ($goldText as $i => $value) {
            $floatValue = floatval($value);
            if (!$floatValue) {
                throw new Exception("Error when parsing: \"" . $value . "\" is not a number");
            }
            $checkedGold[] = $floatValue;
        }
        
        if (!isset($systemFilePath)) {
            return array(null, null, new PointsAndDescription(0.0, "Parsed " . count($checkedGold) . " examples with min value " . min($checkedGold) . " max value " . max($checkedGold)));
        }
        
        $system = self::parseCsv($systemFilePath, false);
        foreach ($system as $i => $value) {
            $floatValue = floatval($value);
            if (!$floatValue) {
                if ($i == 0) {//if header
                    continue; // silently skip header
                } else {
                    throw new Exception("Error when parsing number: \"" . $value . "\" is not a number.");
                }
            }
            $checkedSystem[] = $floatValue;
        }
        if (count($checkedGold) != count($checkedSystem)) {
            throw new Exception("Number of values of system does not match the gold standard. System has " . count($checkedSystem) . " valid example(s) whereas gold standard has " . count($checkedGold) . " examples.");
        }
        return array($checkedGold, $checkedSystem, null);
    }
    
    /**
     * Normalize a value to be in range of zero and one.
     * Everything above or below zero and one will be set to zero or one respectively.
     * @param Float   $value  the value to normalize
     * @param Float   $min  min value
     * @param Float   $max  maxvalue
     * @return normalized value
     */
    private static function normalize($value, $min, $max)
    {
        $normalized = 1 - (($value - $min) / ($max - $min));
        if ($normalized > 1.0) {
            $normalized = 1.0;
        }
        if ($normalized < 0.0) {
            $normalized = 0.0;
        }
        return normalized;
    }
    
    /***********************************
    * CSV Parsing
    ************************************/
    
    /**
     * Parses csv file content.
     * In each line only the last non empty cell is used.
     * If a line contains no non empty cells, this line is skipped.
     * @param String   $filePath  The file path
     * @param Boolean  $removeHeader  True if the first non empty lie should be skipped.
     * @return Array of last non empty cells.
     */
    private static function parseCsv($filePath, $removeHeader = false)
    {
        $text = file_get_contents($filePath);
        if (function_exists('mb_check_encoding')) {
            if (!mb_check_encoding($text, 'UTF-8')) {
                throw new Exception("File is not encoded as UTF-8 - please convert to UTF-8");
            }
        }
        $data = [];
        $splitted = preg_split("/\r\n|\n|\r/", $text); //split newline
        foreach ($splitted as $line) {
            $line = isset($line) ? trim($line) : false;
            if ($line) {
                //extract last non empty value
                $lastNonEmptyCell = "";
                foreach (array_reverse(str_getcsv($line)) as $cell) {
                    $lastNonEmptyCell = trim($cell);
                    if ($lastNonEmptyCell) {
                        break;
                    }
                }
                if ($lastNonEmptyCell) {
                    $data[] = $lastNonEmptyCell;
                }
            }
        }
        if ($removeHeader) {
            array_shift($data);
        }
        return $data; //Make sure that the key of the array is incremented number!!!
    }
    
    
    /************************************
    * CALL EXTERNAL ENDPOINT
    *************************************/
    
    
    /**
     * Calls external endpoint and retrieve the json answer.
     * CURL support has to be enabled (ubuntu: sudo apt-get install php5-curl).
     * @param String   $url  URL to the endpoint.
     * @param Boolean  $removeHeaderOfGoldCsv  True if the first non empty lie should be skipped.
     * @param String  $goldFilePath  Path to the gold file.
     * @param String  $systemFilePath  Path to the system file (optional).
     * @return PointsAndDescription object.
     */
    private static function callEndpoint($url, $removeHeaderOfGoldCsv, $goldFilePath, $systemFilePath = null)
    {
        /*
        global $DIC;
        $lng = $DIC['lng'];
        */
        if (!function_exists('curl_init')) {
            throw new Exception("Php curl support needs to be enabled to call external evaluation service.");
        }
        
        $postFields = array(
            "gold" => curl_file_create($goldFilePath), //curl_file_create needs php version 5.5
            "removeHeader" => $removeHeaderOfGoldCsv,
        );
        if (isset($systemFilePath)) {
            $postFields["system"] = curl_file_create($systemFilePath);
        }
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,        // return content in variable and do not print it
            CURLOPT_HEADER         => false,       // don't show response headers
            CURLOPT_POST           => true,        // POST request
            CURLOPT_POSTFIELDS     => $postFields, // fields which are send via post
            CURLOPT_FOLLOWLOCATION => false,       // do NOT follow redirects
            CURLOPT_USERAGENT      => "Ilias Data Mining Plugin", // name of client
            CURLOPT_CONNECTTIMEOUT => 5,           // time-out (seconds) on connect
            CURLOPT_TIMEOUT        => 20,          // time-out (seconds) on response
            CURLOPT_FAILONERROR    => false,       // do not fail on http error because we want the result with potential error messages
        );
        
        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        
        $result = curl_exec($curl);
        
        $curlErrno = curl_errno($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $defaultErrorMessage = "The evaluation server returned an error. Please contact the course administrator / lecturer. Error: ";
        
        if ($curlErrno) {
            throw new Exception($defaultErrorMessage . "Curl exception (number " . $curlErrno . "): " . curl_strerror($curlErrno), $curlErrno);
        }
        
        
        $jsonResult = json_decode($result);
        if ($jsonResult === null) {
            throw new Exception($defaultErrorMessage . "The server does not send valid json(" . json_last_error_msg() . "). HTTP Status code:" . $httpStatus, json_last_error());
        }
        
        if ($httpStatus !== 200) {
            //error
            $error = $jsonResult->error;
            if (isset($jsonResult->error)) {
                $error = $jsonResult->error;
                if (isset($error->message)) {
                    $message = $error->message;
                    if ($httpStatus === 400) {
                        throw new Exception("Wrong Data: " . $message, $httpStatus);
                    } else {
                        throw new Exception($defaultErrorMessage . "Server message (Statuscode " . $httpStatus . "): " . $message, $httpStatus);
                    }
                }
            }
            throw new Exception($defaultErrorMessage . "Server returned status code " . $httpStatus . " but no correct json error format.", $httpStatus);
        }
        
        $points = 0.0;
        if (isset($systemFilePath)) {
            if (!isset($jsonResult->points)) {
                throw new Exception($defaultErrorMessage . "Server returned status code 200 but no points", 200);
            }
            $points = $jsonResult->points;
            if (!is_int($points) && !is_float($points)) {
                throw new Exception($defaultErrorMessage . "Server returned points which are not a number", 200);
            }
        }
        if (!isset($jsonResult->description)) {
            throw new Exception($defaultErrorMessage . "Server returned status code 200 but no description", 200);
        }
        $description = $jsonResult->description;
        if (!is_string($description)) {
            throw new Exception($defaultErrorMessage . "Server returned description which is not a string", 200);
        }
        return new PointsAndDescription($points, $description);
    }
}

/**************************
* CHECK IF FUNCTION DEFINED
***************************/
if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '')
    {
        return "@$filename;filename="
            . ($postname ?: basename($filename))
            . ($mimetype ? ";type=$mimetype" : '');
    }
}
