<?php

use \PHPUnit\Framework\TestCase;

class WebControllerTest extends TestCase
{
    private $webController;
    private $model;

    protected function setUp() : void
    {
        $globalConfig = new GlobalConfig('/../tests/testconfig.ttl');
        $this->model = Mockery::mock(new Model($globalConfig));
        $this->webController = new WebController($this->model);
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
        $model->shouldReceive('getConfig')
            ->andReturn($globalConfig);
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
        $globalConfig = Mockery::mock('GlobalConfig');
        $globalConfig->shouldReceive('getConfigModifiedTime')
            ->andReturn(1);
        $model = Mockery::mock('Model');
        $model->shouldReceive('getConfig')
            ->andReturn($globalConfig);
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
        $this->assertEquals((new DateTime())->setTimeStamp(1), $configModifiedDate);
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
        $modifiable = Mockery::mock('Modifiable')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $modifiable->shouldReceive('getModifiedDate')
            ->andReturn($concept);
        $controller->shouldReceive('getGitModifiedDate')
            ->andReturn($git);
        $controller->shouldReceive('getConfigModifiedDate')
            ->andReturn($config);
        $returnedValue = $controller->getModifiedDate($modifiable);
        $this->assertEquals($modifiedDate, $returnedValue);
    }

    /**
     * @covers WebController::getChangeList
     * @covers WebController::formatChangeList
     */
    public function testFormatChangeList() {
        $request = new Request($this->model);
        $request->setVocab('changes');
        $request->setLang('en');
        $request->setContentLang('en');
        $request->setQueryParam('offset', '0');

        $changeList = $this->webController->getChangeList($request, 'dc:created');
        $months =$this->webController->formatChangeList($changeList, 'en');

        $expected = array ('hurr durr' => array ('uri' => 'http://www.skosmos.skos/changes/d3', 'prefLabel' => 'Hurr Durr', 'date' => DateTime::__set_state(array('date' => '2010-02-12 10:26:39.000000', 'timezone_type' => 3, 'timezone' => 'UTC')), 'datestring' => 'Feb 12, 2010'), 'second date' => array ('uri' => 'http://www.skosmos.skos/changes/d2', 'prefLabel' => 'Second date', 'date' => DateTime::__set_state(array('date' => '2010-02-12 15:26:39.000000', 'timezone_type' => 3, 'timezone' => 'UTC')), 'datestring' => 'Feb 12, 2010'));

        $this->assertEquals($expected, $months['February 2010']);
    }
}
