Data Mining Question Plugin for ILIAS
==========

This repository contains a [Test Question Plugin](https://docu.ilias.de/goto_docu_pg_64260_42.html) for [ILIAS](https://www.ilias.de).
It allows to generate a specific "Data Mining Question" in an Ilias test. General information about tests in Ilias can be found at the [Ilias Documentation](https://docu.ilias.de/goto_docu_pg_91122_6024.html). 

The idea is similar to a [kaggle competition](https://www.kaggle.com/competitions).
A test creator / teacher can upload a training file for machine learning algorithms as well as a file with features but without predictions.
The test participant can then take the test and upload the predicted values for the given features.
A final score will be automatically computed.
If configured, the participants can also see their position in a Highscore list.
Usually (like in Kaggle competitions) there are two tests: 
1. in the first test, participants can upload their solutions many times and have a highscore to see how well they perform relative to others
 (this allows a bit of overfitting because they can upload and adjust their predictions multiple times)
2. a test with another hold out set which allows only one upload to see how well the model generalizes
Both scenarios can be realized with this plugin by creating two independent tests with the corresponding settings.

This plugin is not restricted to Classification or Regression but can also be extended with a custom evaluation service.
This allows any setting where a gold standard exists and could be automatically evaluated e.g. image detection.

### Installation
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Modules/TestQuestionPool/Questions
cd Customizing/global/plugins/Modules/TestQuestionPool/Questions
git clone https://github.com/dwslab/assDataMiningQuestion
```
As ILIAS administrator, install and activate the plugin in the ILIAS Plugin Administration menu:
1) Administration->Plugins
2) Search for "assDataMiningQuestion" and click on "Actions", then "Install"

### Screenshots

Creating a Data Mining Question:

![Creating a Data Mining Question](doc/createQuestion.png?raw=true "Creating a Data Mining Question")

Take a test:

![Take a test](doc/takeTest.png?raw=true "Take a test")


### Description of Data Mining settings

- Title
    - The title of the question (shown at the top of the test)
- Author
    - Usually prefilled with user name of current user  
- Description
    - Description of the question  
- Question
    - The question text presented below the title e.g.
```Given the training data below, try to create a maschine learning model to make predictions for the given test features.
Please upload the predictions as a csv file. In each line the last non-empty cell is used as a prediction.
Empty lines or lines which only contains empty cells are skipped.
If the first non empty line is not valid it is assumed to be the header.
```
- Working Time
    - the time available to do the test
- Training file
    - a file with training data (it should contain the features as well as the target which is to predict).
    This is optional because the training data can also be provided within the test or just as a link to an external file in the test question.
    If a file is provided a link to this file will be shown when a particpant takes this test.
- Test file containing features
    - a file with test data (it should only contain the features and not the target values). It is optional because you could provide it also in a different way.
- Test file containing target
    - the only required file which should contain only the target values.
- Skip first line
    - if the first non empty line of the test file with target should be skipped or not.
- Evaluation measure
    - Currently nine evaluation measure are implemented (see Available evaluation measures)
- Expected filename ending
    - The expected file name ending of files which the participant should upload. During upload only the files with such an extension are shown. 
      The field can be also left empty for no file extension restriction.
- Maximum upload size
    - The maximum upload size in KB for the participants file (solution / file with participants predictions).
- Points
    - The number of points for this question. Depending on much weight you want to give to this question.

#### Available evaluation measures

- Classification
    - Accuracy
        - The usual accuracy value which also works for multiclass
    - Precision
        - for precision as well as recall and f measure, the average has to be selected (micro, macro weighted or binary). 
          If binary is selected addittionally the positive label has to be provided.
    - Recall
    - F-Measure
- Regression
    - for each of the following measures a minimum and maximum values has to be provided because a score between zero and one has to be calculated. 
    And in regression the error depends on the domain. Usually the minimum error is zero (but can also be higher in case the problem is hard) 
    and the maximum error is usually defined by a baseline. If the error is below the minimum or above the maximum it is set to the min or max value respectively.
    - Max error
    - Mean absolute error
    - Mean squared error (MSE)
    - Root mean squared error (RMSE)
    
- Custom (external evaluation service)
    - An external URL pointing to a REST endpoint which does the evaluation. The interface is defined by a swagger file (live view) and a small python implementation is available in the docs folder.
      You can test the endpoint usually by executing 
      ```
      curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' http://127.0.0.1:41193/metric/precision_micro
      curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' -F 'system=@student.csv' http://127.0.0.1:41193/metric/precision_micro
      ```
    If the external evaluation is unwanted, then comment the lines in class/class.assDataMiningQuestionGUI.php which are surrounded by `if you want to disallow the external evaluation`.


### Important settings of test

#### Show best solution

This option is set to true when creating a new test and will provide a link to the gold standard if not deactivated:
1) Within the test, click on "Settings" and then "Scoring and Results" direct below the "Settings" button.
3) In the chapter "FURTHER DETAILS TO BE INCLUDED IN TEST RESULTS" (de)activate the option "Best Solution" of the first option "Scored Answers of Participant".

#### Activate highscore/ranking

To create a highscore where the participants can see how well they performed in comparison with others, adjust the following settings:
1) Within the test, click on "Settings" and then "Scoring and Results" direct below the "Settings" button.
2) In the chapter "SUMMARY TEST RESULTS" activate the option "Access to Test Results" and choose when the participants can access the ranking.
3) In the chapter "FURTHER DETAILS TO BE INCLUDED IN TEST RESULTS" activate the option "Ranking" and choose the type and length of the ranking.

As a test participant you can access the highscore at the "Info" Tab of the test when clicking the button "Show Ranking" at the top.



### Departments/universities which use this plugin

- [University Mannheim / Data Mining 1](https://www.uni-mannheim.de/dws/teaching/course-details/courses-for-master-candidates/ie-500-data-mining/)


# Development

To download all required development requirements, simply execute `composer install` in the root directory of the plugin.

#### Run tests and coverage report for class calculateEvalMeasure

To run the tests, execute
```
./vendor/bin/phpunit --coverage-html ./coverage --whitelist classes/class.calculateEvalMeasure.php  --bootstrap vendor/autoload.php tests/calculateEvalMeasureTest
```
It will also generate a code coverage report in coverage folder.

#### Run psalm (finding errors)

To run psalm, simply execute
```
./vendor/bin/psalm
```

#### Run PHP CodeSniffer (code style check)

To run codesniffer, simply execute
```
./vendor/bin/phpcs -p --standard=PSR12 classes sql tests plugin.php
```

To automatically fix some errors run:
```
./vendor/bin/phpcbf -p --standard=PSR12 classes sql tests plugin.php
```

#### Check PHP compatibility

Just run
```
./vendor/bin/phpcs -p --standard=PHPCompatibility --runtime-set testVersion 5.6- classes
```

### Authors

This plugin is developed in the course of the [university didactic center in Baden-Württemberg](https://www.hdz-bawue.de) by
- [Sven Hertling](https://www.uni-mannheim.de/dws/people/researchers/phd-students/sven-hertling/)
- [Sebastian Kotthoff](https://www.uni-mannheim.de/dws/people/administration/)
- [Nicolas Heist](https://www.uni-mannheim.de/dws/people/researchers/phd-students/nicolas-heist/)
