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
            # when there is no modified date for a concept, and there is no modified date for the main concept scheme,
            # then it returns null.
            [
                null, # modified date from the concept
                null, # modified date from the main concept scheme
                false, # is the scheme empty?
                false, # is the literal (dc:modified) null?
                null # expected returned modified date
            ], # set #0
            # when there is a modified date for a concept, it is returned immediately. Other values are unimportant.
            [
                $this->datetime('15-Feb-2009'), # modified date from the concept
                null, # modified date from the main concept scheme
                false, # is the scheme empty?
                false, # is the literal (dc:modified) null?
                $this->datetime('15-Feb-2009') # expected returned modified date
            ], # set #1
            # when there is no modified date for a concept, but there is a modified date for the main concept scheme,
            # this last value is then returned.
            [
                null, # modified date from the concept
                $this->datetime('01-Feb-2009'), # modified date from the main concept scheme
                false, # is the scheme empty?
                false, # is the literal (dc:modified) null?
                $this->datetime('01-Feb-2009') # expected returned modified date
            ], # set #2
            # when there is no modified date for a concept, but the concept scheme is returned as empty by the model,
            # then it returns null.
            [
                null, # modified date from the concept
                $this->datetime('01-Feb-2009'), # modified date from the main concept scheme
                true, # is the scheme empty?
                false, # is the literal (dc:modified) null?
                null # expected returned modified date
            ], # set #3
            # when there is no modified date for a concept, there is one non-empty concept scheme, but this one
            # does not have a dc:modified literal, then it returns null
            [
                null, # modified date from the concept
                $this->datetime('01-Feb-2009'), # modified date from the main concept scheme
                false, # is the scheme empty?
                true, # is the literal (dc:modified) null?
                null # expected returned modified date
            ]
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
    public function testGetModifiedDate($conceptDate, $schemeDate, $isSchemeEmpty, $isLiteralNull, $expected)
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
            if ($isLiteralNull) {
                $scheme
                    ->shouldReceive("getLiteral")
                    ->andReturn(null);
            } else {
                $literal = Mockery::mock();
                $scheme
                    ->shouldReceive("getLiteral")
                    ->andReturn($literal);
                $literal
                    ->shouldReceive("getValue")
                    ->andReturn($schemeDate);
            }
        }
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $date = $controller->getModifiedDate($concept, $vocab);
        $this->assertEquals($expected, $date);
    }
}
