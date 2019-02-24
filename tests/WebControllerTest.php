<?php

use \PHPUnit\Framework\TestCase;

class WebControllerTest extends TestCase
{

    /**
     * Data for testConceptGetModifiedDate.
     * @return array
     */
    public function conceptModifiedDateDataProvider()
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
     * Data for testGetGitModifiedDateCacheEnabled and for testGetConfigModifiedDate. We are able to use the
     * same data provider in two methods, as they both have similar interface and behaviour. Only difference
     * being that one retrieves the information from git, and the other from the file system, but as the
     * methods that perform the action are abstracted away, this data provider works for both.
     * @return array
     */
    public function gitAndConfigModifiedDateDataProvider()
    {
        return [
            # cache disabled
            [
                false, # cache enabled
                null, # cached value
                $this->datetime('01-Feb-2009') # modified date time
            ], # set #0
            # cache enabled, but nothing fetched
            [
                true, # cache enabled
                null, # cached value
                $this->datetime('01-Feb-2009') # modified date time
            ], # set #1
            # cache enabled, cached value returned
            [
                true, # cache enabled
                $this->datetime('01-Feb-2009'), # cached value
                null # modified date time
            ], # set #2
        ];
    }

    public function modifiedDateDataProvider()
    {
        return [
            # concept has the most recent date time
            [
                $this->datetime('01-Feb-2011'), # concept
                $this->datetime('01-Feb-2002'), # git
                $this->datetime('01-Feb-2003'), # config
                $this->datetime('01-Feb-2011')  # returned
            ], # set #0
            # concept has the most recent date time
            [
                $this->datetime('01-Feb-2011'), # concept
                null, # git
                $this->datetime('01-Feb-2003'), # config
                $this->datetime('01-Feb-2011')  # returned
            ], # set #1
            # concept has the most recent date time
            [
                $this->datetime('01-Feb-2011'), # concept
                null, # git
                null, # config
                $this->datetime('01-Feb-2011')  # returned
            ], # set #2
            # git has the most recent date time
            [
                $this->datetime('01-Feb-2001'), # concept
                $this->datetime('01-Feb-2012'), # git
                $this->datetime('01-Feb-2003'), # config
                $this->datetime('01-Feb-2012')  # returned
            ], # set #3
            # config has the most recent date time
            [
                $this->datetime('01-Feb-2001'), # concept
                $this->datetime('01-Feb-2002'), # git
                $this->datetime('01-Feb-2013'), # config
                $this->datetime('01-Feb-2013')  # returned
            ], # set #4
            # no date time found
            [
                null, # concept
                null, # git
                null, # config
                null  # returned
            ], # set #4
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
     * @param bool $cacheAvailable
     * @param DateTime|null $cachedValue
     * @param DateTime|null $modifiedDate
     * @dataProvider gitAndConfigModifiedDateDataProvider
     */
    public function testGetGitModifiedDate($cacheAvailable, $cachedValue, $modifiedDate)
    {
        $cache = Mockery::spy('Cache');
        $cache->shouldReceive('isAvailable')
            ->andReturn($cacheAvailable);
        if ($cacheAvailable) {
            $cache->shouldReceive('fetch')
                ->andReturn($cachedValue);
            if ($modifiedDate) {
                $cache->shouldReceive('store')
                    ->andReturn(true);
            }
        }
        $globalConfig = Mockery::mock('GlobalConfig');
        $globalConfig->shouldReceive('getCache')
            ->andReturn($cache);
        $model = Mockery::mock('Model');
        $model->globalConfig = $globalConfig;
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $controller->model = $model;
        if ($modifiedDate) {
            $controller->shouldReceive('executeGitModifiedDateCommand')
                ->andReturn($modifiedDate);
        }
        $gitModifiedDate = $controller->getGitModifiedDate();

        $this->assertNotNull($gitModifiedDate);
        $this->assertTrue($gitModifiedDate > (new DateTime())->setTimeStamp(1));
    }

    /**
     * Execute the git command and test that it returns a valid date time. It should be safe to execute this, as
     * Travis-CI and developer environments should have git installed.
     */
    public function testExecuteGitModifiedDateCommand()
    {
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $gitModifiedDate = $controller->executeGitModifiedDateCommand('git log -1 --date=iso --pretty=format:%cd');
        $this->assertInstanceOf('DateTime', $gitModifiedDate);
        $this->assertTrue($gitModifiedDate > (new DateTime())->setTimeStamp(1));
    }

    /**
     * @param bool $cacheAvailable
     * @param DateTime|null $cachedValue
     * @param DateTime|null $modifiedDate
     * @dataProvider gitAndConfigModifiedDateDataProvider
     */
    public function testGetConfigModifiedDate($cacheAvailable, $cachedValue, $modifiedDate)
    {
        $cache = Mockery::spy('Cache');
        $cache->shouldReceive('isAvailable')
            ->andReturn($cacheAvailable);
        if ($cacheAvailable) {
            $cache->shouldReceive('fetch')
                ->andReturn($cachedValue);
            if ($modifiedDate) {
                $cache->shouldReceive('store')
                    ->andReturn(true);
            }
        }
        $globalConfig = Mockery::mock('GlobalConfig');
        $globalConfig->shouldReceive('getCache')
            ->andReturn($cache);
        $model = Mockery::mock('Model');
        $model->globalConfig = $globalConfig;
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $controller->model = $model;
        if ($modifiedDate) {
            $controller->shouldReceive('retrieveConfigModifiedDate')
                ->andReturn($modifiedDate);
        }
        $configModifiedDate = $controller->getConfigModifiedDate();

        $this->assertNotNull($configModifiedDate);
        $this->assertTrue($configModifiedDate > (new DateTime())->setTimeStamp(1));
    }

    /**
     * Retrieve the modified date of the local config.ttl.dist and test that it returns a valid date time. It should
     * be safe to test this as Travis-CI and developer environments should have a config.ttl.dist file.
     */
    public function testRetrieveConfigModifiedDate()
    {
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $filename = realpath(__DIR__ . "/../config.ttl.dist");
        $dateTime = $controller->retrieveConfigModifiedDate($filename);
        $this->assertInstanceOf('DateTime', $dateTime);
        $this->assertTrue($dateTime > (new DateTime())->setTimeStamp(1));
    }

    /**
     * @param DateTime|null $concept
     * @param DateTime|null $git
     * @param DateTime|null $config
     * @param DateTime|null $modifiedDate
     * @dataProvider modifiedDateDataProvider
     */
    public function testGetModifiedDate($concept, $git, $config, $modifiedDate)
    {
        $controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $controller->shouldReceive('getConceptModifiedDate')
            ->andReturn($concept);
        $controller->shouldReceive('getGitModifiedDate')
            ->andReturn($git);
        $controller->shouldReceive('getConfigModifiedDate')
            ->andReturn($config);
        $concept = Mockery::mock('Concept');
        $vocabulary = Mockery::mock('Vocabulary');
        $returnedValue = $controller->getModifiedDate($concept, $vocabulary);
        $this->assertEquals($modifiedDate, $returnedValue);
    }

    /**
     * Test that the behaviour of getConceptModifiedDate works as expected. If there is a concept with a modified
     * date, then it will return that value. If there is no modified date in the concept, but the main
     * concept scheme contains a date, then the main concept scheme's modified date will be returned instead.
     * Finally, if neither of the previous scenarios occur, then it returns null.
     * @dataProvider conceptModifiedDateDataProvider
     */
    public function testConceptGetModifiedDate($conceptDate, $schemeDate, $isSchemeEmpty, $isLiteralNull, $expected)
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
        $date = $controller->getConceptModifiedDate($concept, $vocab);
        $this->assertEquals($expected, $date);
    }
}
