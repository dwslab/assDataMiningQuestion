Data Mining Question Plugin for ILIAS
==========

This repository contains a [Test Question Plugin](https://docu.ilias.de/goto_docu_pg_64260_42.html) for [ILIAS](https://www.ilias.de).
It allows to generate a "Data Mining Question" in an ILIAS test. For general information about tests in ILIAS, take a look at the [ILIAS Documentation](https://docu.ilias.de/goto_docu_pg_91122_6024.html). 

The idea is similar to a [kaggle competition](https://www.kaggle.com/competitions).
An instructor can upload a training file for machine learning algorithms as well as a file with features but without predictions.
The participant can then take the test and upload the predicted values for the given features. The final score of the test is automatically computed according to a metric specified by the instructor (e.g. Precision, Recall, F1-Score, ..).
If configured, the participants can also see their current performance in a highscore board.
Usually (like in Kaggle competitions) there are two types of tests:
1. Participants can upload their solutions many times and have a highscore to see how well they perform relative to others
 (this allows a bit of overfitting because they can upload and adjust their predictions multiple times)
2. The test has a holdout set which allows only one upload to see how well the model generalizes
Both scenarios can be realized with this plugin by creating two independent tests with the corresponding settings.

This plugin is not restricted to Classification or Regression but can also be extended with a custom evaluation service.
This allows any setting where a gold standard exists and an automatic evaluation is possible (e.g. in image detection).

### Installation
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Modules/TestQuestionPool/Questions
cd Customizing/global/plugins/Modules/TestQuestionPool/Questions
git clone https://github.com/dwslab/assDataMiningQuestion
```
As ILIAS administrator, install and activate the plugin in the ILIAS Plugin Administration menu:
1) Administration->Plugins
2) Search for Plugin "assDataMiningQuestion" (ID is "dataminingqu") and click on "Actions", then "Install"
3) Click on Actions again and choose "Activate"

### Creating a question

To create a data mining question, select a course and add a new test by clicking on "Add New Item" -> "Test" (below category Assessment).
Choose a title for the test (which can contain multiple questions) and click on "Add test" to actually create it.
To add a question, click on the "Questions" tab on the left side and then "Create question".
Select the question type "Data Mining Question" and click on "Create".
Now you can change the properties of the question (every property is described in more detail in the section "Description of Data Mining settings").

Example files for train and test can be found in the [doc/exampleFiles](doc/exampleFiles) directory.
These are generated with the [python script](doc/create_train_test_files.py) in the doc folder.
Afterwards click on "Save" and check the notification at the top of the page to make sure the parsing of the files works as expected.
The test itself has settings that have an impact on the data mining question.
The settings can be found in the "Settings" tab of the test's menu. The relevant settings are explained below in the section "Important settings of test".
For testing the question you have to activate the test by checking the "Online" checkbox in its settings.
After configuring the settings, you can try out the test by clicking on "Start the test" in the "Info" tab.
There you see the question text and a possibility to upload a result file.
After uploading a valid file, click on "Finish the Test" and view the results.
More fine-granular results of the test are shown with a click on "Detailed Results".
For the data mining question you see the uploaded file as well as the best solution which shows a link to the uploaded gold standard.
If you want to change this behaviour, you have to change the test settings.


#### Screenshots

Creating a Data Mining Question:

![Creating a Data Mining Question](doc/createQuestion.png?raw=true "Creating a Data Mining Question")

Take a test:

![Take a test](doc/takeTest.png?raw=true "Take a test")


#### Description of Data Mining settings

- Title
    - The title of the question (shown at the top of the test)
- Author
    - Usually prefilled with user name of current user  
- Description
    - Description of the question  
- Question
    - The question text presented below the title, e.g.
```Given the training data below, try to create a machine learning model to make predictions for the given test features.
Please upload the predictions as a csv file. In each line, the last non-empty cell is used as a prediction.
Empty lines or lines which only contain empty cells are skipped.
If the first non-empty line is not valid it is assumed to be the header.
```
- Working Time
    - The time available to do the test
- Training file
    - A file with training data (it should contain the features as well as the target which should be predicted).
    This is optional because the training data can also be provided within the test or just as a link to an external file in the test question.
    If a file is provided, a link to this file will be shown when a particpant takes this test.
- Test file containing features
    - A file with test data (it should only contain the features and not the target values). 
    It is optional because you could provide it also in a different way (similar to the training file).
- Test file containing target
    - The only required file which should contain only the target values in csv format (one value per line; last non-empty cell is used as target; empty lines are skipped).
- Skip first line
    - If the first non empty line of the test file with target should be skipped or not.
- Evaluation measure
    - Currently nine evaluation measures are implemented (see section "Available evaluation measures").
- Expected filename ending
    - The expected endings of files which the participant should upload. During upload only files with these endings are shown. Specify them as a comma-separated list of file extensions without the dot (e.g. "doc,xls,odt"). The field can be left empty for no file ending restriction.
- Maximum upload size
    - The maximum upload size in KB of the participant's submission file.
- Points
    - The number of points for this question. Depending on how much weight you want to give to this question.

#### Available evaluation measures

- Classification
    * For each of the following measures (except Accuracy), an averaging method has to be selected (micro, macro, weighted or binary). If binary is selected, a positive label has to be provided.
    - Accuracy
    - Precision
    - Recall
    - F-Measure
    
- Regression
    * For each of the following measures minimum and maximum values have to be provided in order to compute a result score between zero and one. In regression the error depends on the domain. Usually the minimum error is zero (but can also be higher in case the problem is hard). The maximum error is usually defined by a baseline. If the error is below the minimum or above the maximum it is set to the lowest or highest possible value, respectively.
    - Max error
    - Mean absolute error
    - Mean squared error (MSE)
    - Root mean squared error (RMSE)
    
- Custom (external evaluation service)
    - An external URL pointing to a REST endpoint which does the evaluation. The interface is defined by a [swagger file](doc/swagger.yaml) ([live view](https://editor.swagger.io/?url=https://raw.githubusercontent.com/dwslab/assDataMiningQuestion/master/doc/swagger.yaml)). A basic [python implementation](doc/restEndpoint.py) for such an endpoint is provided in the docs folder.
      You can test the endpoint by executing 
      ```
      curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' http://127.0.0.1:41193/metric/precision_micro
      curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' -F 'system=@student.csv' http://127.0.0.1:41193/metric/precision_micro
      ```
      To disable the option for an external evaluation service, remove the lines in class/class.assDataMiningQuestionGUI.php which are indicated by `if you want to disallow the external evaluation`.

### Important settings of test

#### Show best solution

This option is set to true by default and will provide the participants a link to the gold standard after submitting a solution if not deactivated:
1) In the test, click on "Settings" and then "Scoring and Results" direct below the "Settings" button.
2) In the section "FURTHER DETAILS TO BE INCLUDED IN TEST RESULTS", deactivate the option "Best Solution" of the first option "Scored Answers of Participant".

#### Activate highscore/ranking

To create a highscore board for the participants, adjust the following settings:
1) In the test, click on "Settings" and then "Scoring and Results" direct below the "Settings" button.
2) In the section "SUMMARY TEST RESULTS", activate the option "Access to Test Results" and choose whether the participants can access the ranking.
3) In the section "FURTHER DETAILS TO BE INCLUDED IN TEST RESULTS", activate the option "Ranking" and choose the type and length of the ranking.

As a test participant you can access the highscore in the test's "Info" tab by clicking the button "Show Ranking" at the top.



### Departments/universities using this plugin

- [University Mannheim / Data Mining 1](https://www.uni-mannheim.de/dws/teaching/course-details/courses-for-master-candidates/ie-500-data-mining/)


# Development

To download all development requirements, run `composer install` in the root directory.

#### Run tests and coverage report for class calculateEvalMeasure

Executing the tests:
```
./vendor/bin/phpunit --coverage-html ./coverage --whitelist classes/class.calculateEvalMeasure.php  --bootstrap vendor/autoload.php tests/calculateEvalMeasureTest
```
A code coverage report is created in the coverage folder.

#### Run psalm (finding errors)

Executing psalm:
```
./vendor/bin/psalm
```

#### Run PHP CodeSniffer (code style check)

Executing codesniffer:
```
./vendor/bin/phpcs -p --standard=PSR12 classes sql tests plugin.php
```

Fixing some of the errors automatically:
```
./vendor/bin/phpcbf -p --standard=PSR12 classes sql tests plugin.php
```

#### Check PHP compatibility

Checking PHP code compatibility:
```
./vendor/bin/phpcs -p --standard=PHPCompatibility --runtime-set testVersion 5.6- classes
```

### Authors

This plugin is developed in the course of the [university didactic center in Baden-Württemberg](https://www.hdz-bawue.de) by
- [Sven Hertling](https://www.uni-mannheim.de/dws/people/researchers/phd-students/sven-hertling/)
- [Sebastian Kotthoff](https://www.uni-mannheim.de/dws/people/administration/)
- [Nicolas Heist](https://www.uni-mannheim.de/dws/people/researchers/phd-students/nicolas-heist/)
