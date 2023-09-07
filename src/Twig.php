<?php

namespace Alghool\Twig;

use Config\Services;
use Alghool\Twig\Config\Twig as TwigConfig;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;

/**
 * Class General
 */
class Twig
{
    /**
     * @var array Paths to Twig templates
     */
    private array $paths = [APPPATH . 'Views'];

    /**
     * @var array Functions to add to Twig
     */
    private array $functions_asis = ['base_url', 'site_url'];

    /**
     * @var array Functions with `is_safe` option
     *
     * @see http://twig.sensiolabs.org/doc/advanced.html#automatic-escaping
     */
    private array $functions_safe = [
        'form_open', 'form_close', 'form_error', 'form_hidden', 'set_value',
    ];

    /**
     * @var array Twig Environment Options
     *
     * @see http://twig.sensiolabs.org/doc/api.html#environment-options
     */
    private array $config = [];

    /**
     * @var bool Whether functions are added or not
     */
    private bool $functions_added = false;

    private ?Environment $twig = null;

    /**
     * @class \Twig\Loader\FilesystemLoader
     */
    private ?LoaderInterface $loader = null;

	/**
	 * @string file extension
	 */
	private string $extension = "twig";

    public function __construct(?TwigConfig $config = null)
    {
        $this->initialize($config);
    }

    public function initialize(?TwigConfig $config = null)
    {
        if (empty($config)) {
            $config = config('Twig');
        }

        if (isset($config->functions_asis)) {
            $this->functions_asis = array_unique(array_merge($this->functions_asis, $config->functions_asis));
        }

        if (isset($config->functions_safe)) {
            $this->functions_safe = array_unique(array_merge($this->functions_safe, $config->functions_safe));
        }

        if (isset($config->paths)) {
            $this->paths = array_unique(array_merge($this->paths, $config->paths));
        }

	    if (isset($config->extension)) {
		    $this->extension = $config->extension?? $this->paths;
	    }

        // default Twig config
        $this->config = [
            'cache'      => WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'twig',
            'debug'      => ENVIRONMENT !== 'production',
            'autoescape' => 'html',
        ];

        return $this;
    }

    public function resetTwig()
    {
        $this->twig = null;
        $this->createTwig();
    }

    protected function createTwig()
    {
        // $this->twig is singleton
        if ($this->twig !== null) {
            return;
        }

        if ($this->loader === null) {
            $this->loader = new FilesystemLoader($this->paths);
        }

        $twig = new Environment($this->loader, $this->config);

        if ($this->config['debug']) {
            $twig->addExtension(new DebugExtension());
        }

        $this->twig = $twig;
    }

    protected function setLoader($loader)
    {
        $this->loader = $loader;
    }

    /**
     * Registers a Global
     *
     * @param string $name  The global name
     * @param mixed  $value The global value
     */
    public function addGlobal($name, $value)
    {
        $this->createTwig();
        $this->twig->addGlobal($name, $value);
    }

	/**
	 * Registers a message
	 *
	 * @param array $msg  new message to be added
	 */
    public function addMsg($msg){
	    $msgs = [];
	    $this->createTwig();
	    $globals = $this->twig->getGlobals();
	    if ( array_key_exists('msgs', $globals) ){
		    $msgs = $globals['msgs'];
	    }
	    $msgs[] = $msg;
	    $this->twig->addGlobal('msgs', $msgs);
    }

    protected function addFunctions()
    {
        // Runs only once
        if ($this->functions_added) {
            return;
        }

        // as is functions
        foreach ($this->functions_asis as $function) {
            if (function_exists($function)) {
                $this->twig->addFunction(new TwigFunction($function, $function));
            }
        }

        // safe functions
        foreach ($this->functions_safe as $function) {
            if (function_exists($function)) {
                $this->twig->addFunction(new TwigFunction($function, $function, ['is_safe' => ['html']]));
            }
        }

        // customized functions
        if (function_exists('anchor')) {
            $this->twig->addFunction(new TwigFunction('anchor', [$this, 'safe_anchor'], ['is_safe' => ['html']]));
        }

        $this->twig->addFunction(new TwigFunction('validation_list_errors', [$this, 'validation_list_errors'], ['is_safe' => ['html']]));

        $this->functions_added = true;
    }

    /**
     * @param string $uri
     * @param string $title
     * @param array  $attributes [changed] only array is acceptable
     */
    public function safe_anchor($uri = '', $title = '', $attributes = []): string
    {
        $uri   = esc($uri, 'url');
        $title = esc($title);

        $new_attr = [];

        foreach ($attributes as $key => $val) {
            $new_attr[esc($key)] = $val;
        }

        return anchor($uri, $title, $new_attr);
    }

    /**
     * @codeCoverageIgnore
     */
    public function validation_list_errors(): string
    {
        return Services::validation()->listErrors();
    }

    public function getTwig(): Environment
    {
        $this->createTwig();

        return $this->twig;
    }

    /**
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Renders Twig Template and Set Output
     *
     * @param string $view   Template filename without `.twig`
     * @param array  $params Array of parameters to pass to the template
     */
    public function display(string $view, array $params = [])
    {
        echo $this->render($view, $params);
    }

    /**
     * Renders Twig Template and Returns as String
     *
     * @param string $view   Template filename without `.twig`
     * @param array  $params Array of parameters to pass to the template
     */
    public function render(string $view, array $params = []): string
    {
        $this->createTwig();
        // We call addFunctions() here, because we must call addFunctions()
        // after loading CodeIgniter functions in a controller.
        $this->addFunctions();

        $view = $view . '.' . $this->extension;

        return $this->twig->render($view, $params);
    }

    public function createTemplate(string $template, array $params = [], bool $display = false)
    {
        $this->createTwig();
        // We call addFunctions() here, because we must call addFunctions()
        // after loading CodeIgniter functions in a controller.
        $this->addFunctions();

        $template = $this->twig->createTemplate($template);

        if( !$display )
        {
            return $template->render($params);
        }
        
        echo $template->render($params);
    }
}
