<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Integrate with an Imageshop account and use Imageshop resources in Craft
 *
 * @link      https://vangenplotz.no/
 * @copyright Copyright (c) 2018 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\variables;

use vangenplotz\imageshop\Imageshop;

use Craft;

/**
 * Imageshop Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.imageshop }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     0.0.1
 */
class ImageshopVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:

     *     {{ craft.imageshop.show(documentId) }}
     *
     * @param null $optional
     * @return string
     */
    public function all($documentsString)
    {
        $documentIds = explode(',', $documentsString);
        $imageArray = [];

        foreach ($documentIds as $documentId) {
            $imageArray[] = Imageshop::$plugin->image->transformImage($documentId);
        }

        return $imageArray;
    }

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:

     *     {{ craft.imageshop.one(documentId) }}
     *
     * @param null $optional
     * @return string
     */
    public function one($documentString)
    {
        $docIds = explode(',', $documentString);

        return Imageshop::$plugin->image->transformImage($docIds[0]);
    }

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:

     *     {{ craft.imageshop.transformImage(documentId, transforms, defaultOptions) }}
     *
     * @param null $optional
     * @return string
     */
    public function transformImage($documentId, $transforms = [], $defaultOptions = [])
    {
        return Imageshop::$plugin->image->transformImage($documentId, $transforms, $defaultOptions);
    }
}
