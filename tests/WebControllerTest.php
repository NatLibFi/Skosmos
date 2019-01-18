<?php

use \PHPUnit\Framework\TestCase;

class WebControllerTest extends TestCase
{

    /**
     * Data for testGetModifiedDate.
     * @return array
     */
    public function modifiedDateDataProvider()
    {
        return [
            [null, null, false, null], # set #0
            [$this->datetime('15-Feb-2009'), null, false, $this->datetime('15-Feb-2009')], # set #1
            [null, $this->datetime('01-Feb-2009'), false, $this->datetime('01-Feb-2009')], # set #2
            [null, $this->datetime('01-Feb-2009'), true, null] # set #3
        ];
    }

    /**
     * Utility method to create a datetime for data provider.
     * @param string $string
     * @return bool|DateTime
     */
    private function datetime(string $string)
    {
        return DateTime::createFromFormat("j-M-Y", $string);
    }

    /**
     * Test that the behaviour of getModifiedDate works as expected. If there is a concept with a modified
     * date, then it will return that value. If there is no modified date in the concept, but the main
     * concept scheme contains a date, then the main concept scheme's modified date will be returned instead.
     * Finally, if neither of the previous scenarios occur, then it returns null.
     * @dataProvider modifiedDateDataProvider
     */
    public function testGetModifiedDate($conceptDate, $schemeDate, $isSchemeEmpty, $expected)
    {
        $concept = Mockery::mock("Concept");
        $concept
            ->shouldReceive("getModifiedDate")
            ->andReturn($conceptDate);
        $vocab = Mockery::mock("Vocabulary");
        // if no scheme date, we return that same value as default concept scheme to stop the flow
        $defaultScheme = (isset($schemeDate) ? "http://test/" : null);
        $vocab
            ->shouldReceive("getDefaultConceptScheme")
            ->andReturn($defaultScheme);
        if (!is_null($schemeDate)) {
            $scheme = Mockery::mock("ConceptScheme");
            $vocab
                ->shouldReceive("getConceptScheme")
                ->andReturn($scheme);
            $scheme
                ->shouldReceive("isEmpty")
                ->andReturn($isSchemeEmpty);
            $literal = Mockery::mock();
            $scheme
                ->shouldReceive("getLiteral")
                ->andReturn($literal);
            $literal
                ->shouldReceive("getValue")
                ->andReturn($schemeDate);
        }
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $date = $controller->getModifiedDate($concept, $vocab);
        $this->assertEquals($expected, $date);
    }
}
