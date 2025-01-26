<?php

declare(strict_types=1);

namespace Axleus\ThemeManager\View\Model;

use Laminas\View\Model\ViewModel;

final class BodyModel extends ViewModel
{
    /**
     * What variable a parent model should capture this model to
     *
     * @var string
     */
    protected $captureTo = 'content';

    /**
     * Template to use when rendering this model
     *
     * @var string
     */
    protected $template = 'layout::body';
}
