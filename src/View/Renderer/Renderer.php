<?php

declare(strict_types=1);

namespace Axleus\ThemeManager\View\Renderer;

use Axleus\Htmx\View\Model;
use Laminas\Stdlib\SplStack;
use Laminas\View\Helper;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\AggregateResolver;
use Mezzio\LaminasView\LaminasViewRenderer;
use Mezzio\LaminasView\NamespacedPathStackResolver;
use Mezzio\Template\ArrayParametersTrait;
use Mezzio\Template\DefaultParamsTrait;
use Mezzio\Template\Exception;
use Mezzio\Template\TemplatePath;

use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;

/**
 * Template implementation bridging laminas/laminas-view.
 *
 * This implementation provides additional capabilities.
 *
 * First, it always ensures the resolver is an AggregateResolver, pushing any
 * non-Aggregate into a new AggregateResolver instance. Additionally, it always
 * registers a NamespacedPathStackResolver at priority 0 (lower than
 * default) in the Aggregate to ensure we can add and resolve namespaced paths.
 */
class Renderer extends LaminasViewRenderer
{
    use ArrayParametersTrait;
    use DefaultParamsTrait;

    private ?ViewModel $layout = null;
    private NamespacedPathStackResolver $resolver;

    /**
     * Constructor
     *
     * Allows specifying the renderer to use (any laminas-view renderer is
     * allowed), and optionally also the layout.
     *
     * The layout may be:
     *
     * - a string layout name
     * - a ModelInterface instance representing the layout
     *
     * If no renderer is provided, a default PhpRenderer instance is created;
     * omitting the layout indicates no layout should be used by default when
     * rendering.
     *
     * @param null|string|ModelInterface $layout
     * @param null|string $defaultSuffix The default template file suffix, if any
     * @throws Exception\InvalidArgumentException For invalid $layout types.
     */
    public function __construct(
        private ?RendererInterface $renderer = null,
        $layout = null,
        ?string $defaultSuffix = null,
        private ?bool $enableHtmx = false
    ) {
        if (null === $renderer) {
            $renderer = $this->createRenderer();
            $resolver = $renderer->resolver();
        } else {
            $resolver = $renderer->resolver();
            if (! $resolver instanceof AggregateResolver) {
                $aggregate = $this->getDefaultResolver();
                $aggregate->attach($resolver);
                $resolver = $aggregate;
            } elseif (! $this->hasNamespacedResolver($resolver)) {
                $this->injectNamespacedResolver($resolver);
            }
        }

        if (is_string($layout) && $layout !== '') {
            $model = new ViewModel();
            $model->setTemplate($layout);
            $layout = $model;
        }

        if ($layout !== null && ! $layout instanceof ModelInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Layout must be a string layout template name or a %s instance; received %s',
                ModelInterface::class,
                get_debug_type($layout),
            ));
        }

        $this->renderer = $renderer;
        $this->resolver = $this->getNamespacedResolver($resolver);
        if (null !== $defaultSuffix) {
            $this->resolver->setDefaultSuffix($defaultSuffix);
        }
        $this->layout = $layout;
    }

    /**
     * Render a template with the given parameters.
     *
     * If a layout was specified during construction, it will be used;
     * alternately, you can specify a layout to use via the "layout"
     * parameter/variable, using either:
     *
     * - a string layout template name
     * - a Laminas\View\Model\ModelInterface instance
     *
     * Layouts specified with $params take precedence over layouts passed to
     *
     * @param array|ModelInterface|object $params
     */
    public function render(string $name, $params = []): string
    {
        $viewModel = $params instanceof ModelInterface
            ? $this->mergeViewModel($name, $params)
            : $this->createModel($name, $params);

        $useLayout = false !== $viewModel->getVariable('layout', null);
        if ($useLayout) {
            $viewModel = $this->prepareLayout($viewModel);
        }

        return $this->renderModel($viewModel, $this->renderer);
    }

    /**
     * Add a path for templates.
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        $this->resolver->addPath($path, $namespace);
    }

    /**
     * Get the template directories
     *
     * @return TemplatePath[]
     */
    public function getPaths(): array
    {
        $paths = [];

        /**
         * @var array<array-key, SplStack<string>> $pathStack
         */
        $pathStack = $this->resolver->getPaths();
        foreach ($pathStack as $namespace => $namespacedPaths) {
            if (
                $namespace === NamespacedPathStackResolver::DEFAULT_NAMESPACE
                || empty($namespace)
                || is_int($namespace)
            ) {
                $namespace = null;
            }

            foreach ($namespacedPaths as $path) {
                $paths[] = new TemplatePath($path, $namespace);
            }
        }

        return $paths;
    }

    /**
     * Create a view model from the template and parameters.
     *
     * @param string $name
     * @return ModelInterface
     */
    private function createModel($name, mixed $params)
    {
        $params = $this->mergeParams($name, $this->normalizeParams($params));
        $model  = new ViewModel($params);
        $model->setTemplate($name);

        if ($this->enableHtmx) {
            $body = new Model\BodyModel();
            $body->addChild(new Model\HeaderModel());
            $body->addChild(new Model\FooterModel());
            $body->addChild($model);
            return $body;
        }

        return $model;
    }

    /**
     * Do a recursive, depth-first rendering of a view model.
     *
     * @throws Exception\RenderingException If it encounters a terminal child.
     */
    private function renderModel(
        ModelInterface $model,
        RendererInterface $renderer,
        ?ModelInterface $root = null
    ): string {
        if (! $root) {
            $root = $model;
        }

        foreach ($model as $child) {
            if ($child->terminate()) {
                throw new Exception\RenderingException('Cannot render; encountered a child marked terminal');
            }

            $capture = $child->captureTo();
            if (empty($capture)) {
                continue;
            }

            $child = $this->mergeViewModel($child->getTemplate(), $child);

            if ($child !== $root) {
                $viewModelHelper = $renderer->plugin(Helper\ViewModel::class);
                $viewModelHelper->setRoot($root);
            }

            $result = $this->renderModel($child, $renderer, $root);

            if ($child->isAppend()) {
                $oldResult = $model->{$capture};
                $model->setVariable($capture, $oldResult . $result);
                continue;
            }

            $model->setVariable($capture, $result);
        }

        return $renderer->render($model);
    }

    /**
     * Returns a PhpRenderer object
     */
    private function createRenderer(): PhpRenderer
    {
        $renderer = new PhpRenderer();
        $renderer->setResolver($this->getDefaultResolver());
        return $renderer;
    }

    /**
     * Get the default resolver
     */
    private function getDefaultResolver(): AggregateResolver
    {
        $resolver = new AggregateResolver();
        $this->injectNamespacedResolver($resolver);
        return $resolver;
    }

    /**
     * Attaches a new NamespacedPathStackResolver to the AggregateResolver
     *
     * A priority of 0 is used, to ensure it is the last queried.
     */
    private function injectNamespacedResolver(AggregateResolver $aggregate): void
    {
        $aggregate->attach(new NamespacedPathStackResolver(), 0);
    }

    private function hasNamespacedResolver(AggregateResolver $aggregate): bool
    {
        return $this->getNamespacedResolver($aggregate) !== null;
    }

    private function getNamespacedResolver(AggregateResolver $aggregate): ?NamespacedPathStackResolver
    {
        foreach ($aggregate as $resolver) {
            if ($resolver instanceof NamespacedPathStackResolver) {
                return $resolver;
            }
        }

        return null;
    }

    /**
     * Merge global/template parameters with provided view model.
     *
     * @param string $name Template name.
     */
    private function mergeViewModel(string $name, ModelInterface $model): ModelInterface
    {
        $params = $this->mergeParams(
            $name,
            $this->normalizeParams($model->getVariables())
        );
        $model->setVariables($params);
        $model->setTemplate($name);
        return $model;
    }

    /**
     * Prepare the layout, if any.
     *
     * Injects the view model in the layout view model, if present.
     *
     * If the view model contains a non-empty 'layout' variable, that value
     * will be used to seed a layout view model, if:
     *
     * - it is a string layout template name
     * - it is a ModelInterface instance
     *
     * If a layout is discovered in this way, it will override the one set in
     * the constructor, if any.
     *
     * Returns the provided $viewModel unchanged if no layout is discovered;
     * otherwise, a view model representing the layout, with the provided
     * view model as a child, is returned.
     */
    private function prepareLayout(ModelInterface $viewModel): ModelInterface
    {
        $providedLayout = $viewModel->getVariable('layout', null);
        if (is_string($providedLayout) && ! empty($providedLayout)) {
            $layout = new ViewModel();
            $layout->setTemplate($providedLayout);
            $viewModel->setVariable('layout', null);
        } elseif ($providedLayout instanceof ModelInterface) {
            $layout = $providedLayout;
            $viewModel->setVariable('layout', null);
        } else {
            $layout = $this->layout ? clone $this->layout : null;
        }

        if ($layout) {
            $layout->addChild($viewModel);
            $viewModel = $layout;
            $viewModel->setVariables($this->mergeParams($layout->getTemplate(), (array) $layout->getVariables()));
        }

        return $viewModel;
    }
}
