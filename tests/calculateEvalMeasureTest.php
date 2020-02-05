<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CalculateTest extends TestCase
{
    private const BASEPATH = 'tests/files/';
    public function setUp(): void
    {
        //create language mock which will return the id
        $lng_mock = $this->getMockBuilder(stdClass::class)->setMethods(['txt'])->getMock();
        $lng_mock->method('txt')->will($this->returnArgument(0));
    //set language mock to globals
        global $DIC;
        unset($DIC['lng']);
        $DIC['lng'] = $lng_mock;
    }
    
    public function testAccuracy(): void
    {
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv'
        );
        $this->assertStringStartsWith('Parsed 30 examples with 3 distinct values', $p->getDescription());
        
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
        $this->assertEquals(1.0, $p->GetPoints());
        
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v2.csv'
        );
        $this->assertEquals(1.0, $p->GetPoints());
        
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_some_prediction_errors.csv'
        );
        $this->assertEquals(0.9333, $p->GetPoints());
    }
    
    public function testAccuracyWrongFormat(): void
    {
        if (function_exists('mb_check_encoding')) {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage("File is not encoded as UTF-8");
            $p = calculateEvalMeasure::calculate(
                "accuracy",
                true,
                1,
                null,
                null,
                null,
                null,
                null,
                self::BASEPATH . 'classificaton_iris_test_gold.csv',
                self::BASEPATH . 'classificaton_iris_system_wrong_format.csv'
            );
        }
    }
    
    public function testAccuracyWrongCount(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Number of values of system does not match the gold standard");
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_wrong_count.csv'
        );
    }
    
    public function testAccuracyWrongLabel(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Error when parsing csv");
        $p = calculateEvalMeasure::calculate(
            "accuracy",
            true,
            1,
            null,
            null,
            null,
            null,
            null,
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_wrong_labels.csv'
        );
    }
    
    
    
    
    /*******************
    * Test Endpoint
    *******************/
    
    public function testEndpoint(): void
    {
        // 200 OK body: Hello World => http://www.mocky.io/v2/5df633fe3400006d00e5a53d
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("does not send valid json");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5df633fe3400006d00e5a53d',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointOnlyPoints(): void
    {
        // 200 OK body: {"points":0.52647} => http://www.mocky.io/v2/5e04887b3100002c00fd2f6a
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("no description");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04887b3100002c00fd2f6a',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
        //$this->assertEquals(0.5265,$p->GetPoints());
    }
    
    public function testEndpointOK(): void
    {
        // 200 OK body: {"points":0.52647, "description": "Test"} => http://www.mocky.io/v2/5e04a8ab3100005f00fd3088
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04a8ab3100005f00fd3088',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
        $this->assertEquals(0.5265, $p->GetPoints());
        $this->assertEquals("Test", $p->getDescription());
    }
    
    public function testEndpointNoPoints(): void
    {
        // 200 OK body: {"value":0.52647} => http://www.mocky.io/v2/5df6314d3400006d00e5a53a
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("no points");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5df6314d3400006d00e5a53a',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointError400(): void
    {
        // 400 body: {"error":{"message":"Test"}} => http://www.mocky.io/v2/5e04ba143100005700fd30ed
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Wrong Data: Test");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04ba143100005700fd30ed',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointError500(): void
    {
        // 500 body: {"error":{"message":"Test"}} => http://www.mocky.io/v2/5e04bacf3100004d00fd30f1
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Server message (Statuscode 500): Test");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04bacf3100004d00fd30f1',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointError500WrongMessage(): void
    {
        // 500 body: {"error":"foo"} => http://www.mocky.io/v2/5e04bd403100006e66fd30fd
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Server returned status code 500 but no correct json error format");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04bd403100006e66fd30fd',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointPointNoFloat(): void
    {
        // 200 body: {"points":"hello", "description": "Test"} => http://www.mocky.io/v2/5e04c4be31000039befd3130
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Server returned points which are not a number");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04c4be31000039befd3130',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointDescriptionNoString(): void
    {
        // 200 body: {"points":0.51, "description": 0.51} => http://www.mocky.io/v2/5e04c53c31000039befd3133
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Server returned description which is not a string");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04c53c31000039befd3133',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointWrongURL(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Curl exception (number 3)");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'foo bar',
            self::BASEPATH . 'classificaton_iris_test_gold.csv'
        );
    }
    
    public function testEndpointWrongPointRange(): void
    {
        // 200 OK body: {"points":1.01, "description": "Test"} => http://www.mocky.io/v2/5e04c5973100004d00fd3134
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Computed measure is greater one or smaller zero. Value:");
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04c5973100004d00fd3134',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
    }
    
    public function testEndpointTooLongDescription(): void
    {
        // 200 OK body: {"points":1.01, "description": "long text generated"}
        // => http://www.mocky.io/v2/5e04c6d7310000705ffd313a
        $p = calculateEvalMeasure::calculate(
            "custom",
            true,
            1,
            null,
            null,
            null,
            null,
            'http://www.mocky.io/v2/5e04c6d7310000705ffd313a',
            self::BASEPATH . 'classificaton_iris_test_gold.csv',
            self::BASEPATH . 'classificaton_iris_system_correct_v1.csv'
        );
        $this->assertStringEndsWith('...', $p->getDescription());
    }
    
    public function testWrongEvalMethod(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Evaluation method is not implemented");
        $p = calculateEvalMeasure::calculate("foo", true, 1, null, null, null, null, null, null, null);
    }
}
