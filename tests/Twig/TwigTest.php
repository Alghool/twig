<?php

namespace Tests\Twig;

use Alghool\Twig\Config\Twig as TwigConfig;
use Twig\Environment;
use Alghool\Twig\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Alghool\Twig\Twig;

class TwigTest extends CIUnitTestCase
{
    protected TwigConfig $config;
    protected Twig $twig;
    
    protected function setUp(): void
    {
        helper(['url', 'form', 'twig_helper']);

        parent::setUp();

        $this->config = new TwigConfig();
        $this->config->paths = [ './tests/_support/templates/' ];
        $this->config->functions_asis = [ 'md5' ];

        $this->twig = new Twig( $this->config );
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testConstructDefault()
    {
        $this->twig = new Twig();

        $this->assertInstanceOf( Environment::class, $this->twig->getTwig());
        $this->assertCount( 1, $this->twig->getPaths());
    }

    public function testConstructCustomConfig()
    {
        $this->assertInstanceOf( Environment::class, $this->twig->getTwig());
        $this->assertCount( 2, $this->twig->getPaths());
    }

    public function testConstructAsAService()
    {
        $this->twig = Services::twig(null, false);

        $this->assertInstanceOf( Environment::class, $this->twig->getTwig());
        $this->assertCount( 1, $this->twig->getPaths());
    }

    public function testConstructAsAServiceCustomConfig()
    {
        $this->twig = Services::twig( $this->config, false );

        $this->assertInstanceOf( Environment::class, $this->twig->getTwig());
        $this->assertCount( 2, $this->twig->getPaths());
    }

    public function testConstructAsAHelper()
    {
        $this->twig = twig_instance();

        $this->assertInstanceOf( Environment::class, $this->twig->getTwig());
        $this->assertCount( 1, $this->twig->getPaths());
    }

    public function testRender()
    {
        $data = [
            'name' => 'CodeIgniter',
        ];
        $output = $this->twig->render('welcome', $data);
        $this->assertEquals('Hello CodeIgniter!' . "\n", $output);
    }

    public function testDisplay()
    {
        $data = [
            'name' => 'CodeIgniter',
        ];

        $this->twig->display('welcome', $data);

        $this->expectOutputString('Hello CodeIgniter!' . "\n");
    }

    public function testCreateTemplate()
    {
        $data = [
            'name' => 'CodeIgniter',
        ];

        echo $this->twig->createTemplate('Hello {{ name }}!', $data, false);

        $this->expectOutputString('Hello CodeIgniter!');
    }

    public function testCreateTemplateDsiplay()
    {
        $data = [
            'name' => 'CodeIgniter',
        ];

        $this->twig->createTemplate('Hello {{ name }}!', $data, true);

        $this->expectOutputString('Hello CodeIgniter!');
    }

    public function testAddGlobal()
    {
        $this->twig->addGlobal('sitename', 'Global');

        $output = $this->twig->render('global');
        $this->assertEquals('<title>Global</title>' . "\n", $output);
    }

    public function testAddFunctionsRunsOnlyOnce()
    {
        $data = [
            'name' => 'CodeIgniter',
        ];

        $this->assertFalse($this->getPrivateProperty($this->twig, 'functions_added'));

        $output = $this->twig->render('welcome', $data);

        $this->assertEquals('Hello CodeIgniter!' . "\n", $output);
        $this->assertTrue($this->getPrivateProperty($this->twig, 'functions_added'));

        // Calls render() twice
        $output = $this->twig->render('welcome', $data);

        $this->assertEquals('Hello CodeIgniter!' . "\n", $output);
        $this->assertTrue($this->getPrivateProperty($this->twig, 'functions_added'));
    }

    public function testFunctionAsIs()
    {
        $output = $this->twig->render('functions_asis');
        $this->assertEquals('900150983cd24fb0d6963f7d28e17f72' . "\n", $output);
    }

    public function testFunctionSafe()
    {
        $this->config->functions_safe = [ 'functionSafe' ];

        $this->twig->initialize( $this->config );

        $output = $this->twig->render('functions_safe');
        $this->assertEquals('<s>test</s>' . "\n", $output);
    }
}
