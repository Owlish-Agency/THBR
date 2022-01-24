<?php
/**
 * Twig Helpers plugin for Craft CMS
 *
 * Twig Helpers Twig Extension
 *
 * --snip--
 * Twig can be extended in many ways; you can add extra tags, filters, tests, operators, global variables, and
 * functions. You can even extend the parser itself with node visitors.
 *
 * http://twig.sensiolabs.org/doc/advanced.html
 * --snip--
 *
 * @author    Stephen Bowling
 * @copyright Copyright (c) 2016 Stephen Bowling
 * @link      http://stephenbowling.com
 * @package   TwigHelpers
 * @since     1.0.0
 */

namespace Craft;

use Twig_Extension;
use Twig_Filter_Method;

class TwigHelpersTwigExtension extends \Twig_Extension
{
    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'TwigHelpers';
    }

    /**
     * Returns an array of Twig functions, used in Twig templates via:
     *
     *      {% set this = someFunction('something') %}
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'getUUID' => new \Twig_Function_Method($this, 'getUUID'),
        );
    }

    /**
     * Our function called via Twig; it can do anything you want
     *
      * @return string
     */
    public function getUUID()
    {
        return StringHelper::UUID();
    }
}
