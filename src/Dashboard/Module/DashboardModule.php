<?php
namespace CrudView\Dashboard\Module;

use Cake\View\Exception\MissingTemplateException;
use Cake\View\ViewVarsTrait;
use CrudView\Dashboard\Traits\DashboardTrait;
use Error;
use Exception;
use ReflectionMethod;

abstract class DashboardModule
{
    use DashboardTrait, ViewVarsTrait {
        DashboardTrait::set insteadof ViewVarsTrait;
        ViewVarsTrait::set as viewVarsSet;
    }

    /**
     * Caching setup.
     *
     * @var array|bool
     */
    protected $_cache = false;

    /**
     * Constructor
     *
     * @param string $title The name of the module
     */
    public function __construct($title)
    {
        $this->setValid([
            'title',
        ]);

        $this->set('title', $title);
        $this->action = 'dashboard';
        $this->template = 'dashboard';
    }

    /**
     * Render the cell.
     *
     * @param string|null $template Custom template name to render. If not provided (null), the last
     * value will be used. This value is automatically set by `CellTrait::cell()`.
     * @return string The rendered cell.
     * @throws \Cake\View\Exception\MissingTemplateException When a MissingTemplateException is raised during rendering.
     */
    public function render($template = null)
    {
        if ($template === null) {
            $className = get_class($this);
            $namePrefix = '\Dashboard\Module\\';
            $template = substr($className, strpos($className, $namePrefix) + strlen($namePrefix));
            $template = substr($template, 0, -6);
        }

        $cache = [];
        if ($this->_cache) {
            $cache = $this->_cacheConfig($template);
        }

        $render = function () use ($template) {
            $builder = $this->viewBuilder();

            $builder->setLayout(false)
                ->setTemplate($template);
            if (!$builder->getTemplatePath()) {
                $builder->setTemplatePath('Element' . DIRECTORY_SEPARATOR . 'dashboard');
            }

            $this->viewVarsSet($this->_properties);
            $this->View = $this->createView();

            return $this->View->render('CrudView.' . $template);
        };

        if ($cache) {
            return Cache::remember($cache['key'], $render, $cache['config']);
        }

        return $render();
    }

    /**
     * Generate the cache key to use for this cell.
     *
     * If the key is undefined, the module class will be used.
     *
     * @param string|null $template The name of the template to be rendered.
     * @return array The cache configuration.
     */
    protected function _cacheConfig($template = null)
    {
        if (empty($this->_cache)) {
            return [];
        }
        $template = $template ?: 'default';
        $key = 'dashboard_' . Inflector::underscore(get_class($this)) . '_' . $template;
        $key = str_replace('\\', '_', $key);
        $default = [
            'config' => 'default',
            'key' => $key
        ];
        if ($this->_cache === true) {
            return $default;
        }

        return $this->_cache + $default;
    }

    /**
     * Magic method.
     *
     * Starts the rendering process when Module is echoed.
     *
     * *Note* This method will trigger an error when view rendering has a problem.
     * This is because PHP will not allow a __toString() method to throw an exception.
     *
     * @return string Rendered cell
     * @throws \Error Include error details for PHP 7 fatal errors.
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            trigger_error(sprintf('Could not render cell - %s [%s, line %d]', $e->getMessage(), $e->getFile(), $e->getLine()), E_USER_WARNING);

            return '';
        } catch (Error $e) {
            throw new Error(sprintf('Could not render cell - %s [%s, line %d]', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }
}
