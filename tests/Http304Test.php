<?php

use \PHPUnit\Framework\TestCase;

class Http304Test extends TestCase
{

    /**
     * @var \Mockery\Mock|Model
     */
    private $model;
    /**
     * @var \Mockery\Mock|Vocabulary
     */
    private $vocab;
    /**
     * @var \Mockery\Mock|WebController
     */
    private $controller;
    /**
     * @var \Mockery\Mock|Request
     */
    private $request;
    /**
     * @var \Mockery\Mock|Twig_Environment
     */
    private $twig;

    /**
     * Initializes the test objects. Not using setUp here as the $vocabularyName
     * needs to be specified per test.
     * @param $vocabularyName string name of the vocabulary used for this test
     * @throws Exception if any error occurs during vocabulary creation
     */
    public function initObjects(string $vocabularyName)
    {
        putenv("LANGUAGE=en_GB.utf8");
        putenv("LC_ALL=en_GB.utf8");
        setlocale(LC_ALL, 'en_GB.utf8');
        bindtextdomain('skosmos', 'resource/translations');
        bind_textdomain_codeset('skosmos', 'UTF-8');
        textdomain('skosmos');
        $this->model = Mockery::mock(new Model(new GlobalConfig('/../tests/testconfig.ttl')))->makePartial();
        $this->vocab = Mockery::mock($this->model->getVocabulary($vocabularyName))->makePartial();
        $this->controller = Mockery::mock('WebController')
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->controller->model = $this->model;
        $this->request = Mockery::mock('Request');
        $this->request->allows([
            "getLang" => "en",
            "getContentLang" => "en",
            "getVocab" => $this->vocab
        ]);
        $this->twig = Mockery::mock("Twig_Environment")->makePartial();
        $mockedTemplate = Mockery::mock();
        $mockedTemplate->shouldReceive("render")->andReturn("rendered");
        $this->twig->allows([
            "loadTemplate" => $mockedTemplate
        ]);
        $this->controller->twig = $this->twig;
    }

    /**
     * Test that a vocabulary that has disabled the use of HTTP 304, never invokes the
     * method that sends these HTTP headers.
     * @throws Exception
     */
    public function testHttp304NotUsedWhenDisabled()
    {
        $this->initObjects("http304disabled");

        $this->controller
            ->shouldReceive("setLanguageProperties")
            ->withArgs(["en"]);
        $this->request
            ->shouldReceive("getURI")
            ->andReturn("");
        $this->vocab
            ->shouldReceive("getConceptURI")
            ->andReturn("");

        $concepts = [];
        $concept = Mockery::mock("Concept")->makePartial();
        $concept->allows([
            "getType" => ["skos:Concept"]
        ]);
        $concept->shouldReceive("getVocab")
            ->andReturn($this->vocab);
        $concepts[] = $concept;
        $this->vocab
            ->shouldReceive("getConceptInfo")
            ->andReturn($concepts);
        $this->vocab
            ->shouldReceive("getBreadCrumbs")
            ->andReturn([
                "breadcrumbs" => "",
                "combined" => ""
            ]);

        $this->controller->shouldNotReceive("http304disabled");
        $this->controller->shouldNotReceive("sendHeader");

        ob_start();
        $this->controller->invokeVocabularyConcept($this->request);
        $content = ob_get_clean();
        $this->assertEquals("rendered", $content);
    }

    /**
     * Test that on the first ever request, the real content is sent, and not just
     * the HTTP 304 response.
     * @throws Exception
     */
    public function testHttp304FirstEverRequest()
    {
        $this->initObjects("http304");

        $this->controller
            ->shouldReceive("setLanguageProperties")
            ->withArgs(["en"]);
        $this->request
            ->shouldReceive("getURI")
            ->andReturn("");
        $this->vocab
            ->shouldReceive("getConceptURI")
            ->andReturn("");

        $concepts = [];
        $concept = Mockery::mock("Concept")->makePartial();
        $concept->allows([
            "getType" => ["skos:Concept"]
        ]);
        $concept->shouldReceive("getVocab")
            ->andReturn($this->vocab);
        $concepts[] = $concept;
        $this->vocab
            ->shouldReceive("getConceptInfo")
            ->andReturn($concepts);
        $this->vocab
            ->shouldReceive("getBreadCrumbs")
            ->andReturn([
                "breadcrumbs" => "",
                "combined" => ""
            ]);

        // the main logic of this test
        {
            $modifiedDate = DateTime::createFromFormat('j-M-Y', '15-Feb-2009');
            $this->controller
                ->shouldReceive("getModifiedDate")
                ->once()
                ->andReturn($modifiedDate);
            $this->controller
                ->shouldReceive("sendHeader")
                ->once()
                ->withArgs(["Last-Modified: " . $modifiedDate->format('D, d M Y H:i:s \G\M\T')])
                ->andReturn(true);
        }

        ob_start();
        $this->controller->invokeVocabularyConcept($this->request);
        $content = ob_get_clean();
        $this->assertEquals("rendered", $content);
    }

    /**
     * Test that upon receiving a request with the right headers, the controller returns an HTTP 304 response.
     * @throws Exception
     */
    public function testHttp304()
    {
        $this->initObjects("http304");

        $this->controller
            ->shouldReceive("setLanguageProperties")
            ->withArgs(["en"]);
        $this->request
            ->shouldReceive("getURI")
            ->andReturn("");
        $this->vocab
            ->shouldReceive("getConceptURI")
            ->andReturn("");

        $concepts = [];
        $concept = Mockery::mock("Concept")->makePartial();
        $concept->allows([
            "getType" => ["skos:Concept"]
        ]);
        $concept->shouldReceive("getVocab")
            ->andReturn($this->vocab);
        $concepts[] = $concept;
        $this->vocab
            ->shouldReceive("getConceptInfo")
            ->andReturn($concepts);
        $this->vocab
            ->shouldReceive("getBreadCrumbs")
            ->andReturn([
                "breadcrumbs" => "",
                "combined" => ""
            ]);

        // the main logic of this test
        {
            $modifiedDate = DateTime::createFromFormat('j-M-Y', '15-Feb-2009');
            $ifModifiedSince = DateTime::createFromFormat('j-M-Y', '15-Feb-2019');
            $this->controller
                ->shouldReceive("getModifiedDate")
                ->once()
                ->andReturn($modifiedDate);
            $this->controller
                ->shouldReceive("getIfModifiedSince")
                ->once()
                ->andReturn($ifModifiedSince);
            $this->controller
                ->shouldReceive("sendHeader")
                ->once()
                ->withArgs(["Last-Modified: " . $modifiedDate->format('D, d M Y H:i:s \G\M\T')])
                ->andReturn(true);
            $this->controller
                ->shouldReceive("sendHeader")
                ->once()
                ->withArgs(["HTTP/1.0 304 Not Modified"])
                ->andReturn(true);
        }

        ob_start();
        $this->controller->invokeVocabularyConcept($this->request);
        $content = ob_get_clean();
        $this->assertEquals("", $content);
    }

    public function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
