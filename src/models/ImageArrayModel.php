<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Integrate with an Imageshop account and use Imageshop resources in Craft
 *
 * @link      https://vangenplotz.no/
 * @copyright Copyright (c) 2018 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\models;

use vangenplotz\imageshop\Imageshop;

use Craft;
use craft\base\Model;

/**
 * Imageshop ImageModel Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     0.0.1
 */
class ImageArrayModel extends Model
{
    // Public Properties
    // =========================================================================


    /**
     * Images array
     *
     * @var array
     */
    protected $images;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param $image
     *
     * @throws Exception
     */
    public function __construct($valueString)
    {
        parent::__construct();

        $documents = explode(',', $valueString);
        $imageArray = [];

        foreach ($documents as $document) {
            $imageModel = Imageshop::$plugin->image->transformImage($document);

            if($imageModel)
            {
                $imageArray[] = $imageModel;
            }
        }


        $this->images = $imageArray;
    }


    /**
     * @return array|null
     */
    public function all()
    {
        return $this->images;
    }

    /**
     * @return ImageModel|null
     */
    public function one($index = 0)
    {
        return count($this->images) ? $this->images[$index] : null;
    }


    /**
     * @param $width int|string us when selecting one amongst a set of transforms
     *
     * @return array|string
     */
    public function transform($transform = null, $defaultOptions = null)
    {
        foreach ($this->images as $imageModel) {
            $imageModel->transform($transform, $defaultOptions);
        }

        return $this->all();
    }
}
