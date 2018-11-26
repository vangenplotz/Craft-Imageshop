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
class ImageModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Image alt text from Imageshop document Description
     *
     * @var string
     */
    public $alt = '';

    /**
     * Image credits from Imageshop document Credits
     *
     * @var string
     */
    public $credits = '';

    /**
     * Full height of original
     *
     * @var int
     */
    public $originalHeight = 0;

     /**
     * Full width of original
     *
     * @var int
     */
    public $originalWidth = 0;

    /**
     * Image data
     *
     * @var array
     */
    public $imageData = [];

    /**
     * Image rights from Imageshop document Rights
     *
     * @var string
     */
    public $rights = '';

    /**
     * Transformed image array
     *
     * @var array
     */
    public $transformed = [];

    /**
     * Image title from Imageshop Name
     *
     * @var string
     */
    public $title = '';


    // Protected Properties
    // =========================================================================

    /**
     * Document id
     *
     * @var string
     */
    protected $documentId;

    /**
     * Document interface
     *
     * @var string
     */
    protected $documentInterface;

    /**
     * Document language
     *
     * @var string
     */
    protected $documentLanguage;

    /**
     * Transforms array
     *
     * @var array
     */
    protected $transforms;


    /**
     * Default options array
     *
     * @var array
     */
    protected $defaultOptions;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param $image
     *
     * @throws Exception
     */
    public function __construct($documentId, $documentInterface, $documentLanguage, $transforms = null, $defaultOptions = [])
    {
        parent::__construct();

        $this->documentId = $documentId;
        $this->documentInterface = $documentInterface;
        $this->documentLanguage = $documentLanguage;

        $imageData = Imageshop::$plugin->image->getImageData($documentId);
        $originalSubdocumet = $imageData->SubDocumentList->V4SubDocument[0];
        $this->imageData = $imageData;
        $this->alt = isset($imageData->Description) && is_string($imageData->Description) ? $imageData->Description : "";
        $this->credits = isset($imageData->Credits) && is_string($imageData->Credits)  ? $imageData->Credits : "";
        $this->originalWidth = $originalSubdocumet->Width;
        $this->originalHeight = $originalSubdocumet->Height;
        $this->rights = isset($imageData->Rights) && is_string($imageData->Rights)  ? $imageData->Rights : "";
        $this->title = isset($imageData->Name) && is_string($imageData->Name)  ? $imageData->Name : "";

        $this->transforms = $transforms;
        $this->defaultOptions = $defaultOptions;

        $this->transform($transforms);
    }


    /**
     * @return mixed|null
     */
    public function getUrl($transform = null)
    {
        if( is_int($transform) ) {
            $width = $transform;
            $transform = [];
            $transform['width'] = $width;
        }

        // Shorthand for using getUrl similar to normal Craft Asset
        if( $transform )
        {
            $transform = array_merge($this->defaultOptions, $transform);
            $transform = $this->calculateTargetSizeFromRatio($transform);
            return $this->buildTransform($this->documentId, $transform);
        }

        if ($image = $this->transformed) {
            if ($image && isset($image['url'])) {
                return $image['url'];
            }
        }
        return null;
    }

    /**
     * @return float|null
     */
    public function ratio()
    {
        if( $this->originalWidth == 0 || $this->originalHeight == 0 )
        {
            return null;
        }

        return $this->originalWidth / $this->originalHeight;
    }


    /**
     * @param $width int|string us when selecting one amongst a set of transforms
     *
     * @return null|string
     */
    public function src($width = null)
    {
        if ($image = $this->transformed)
        {
            if( isset($image[0]) )
            {
                if( $width )
                {
                    foreach ($image as $imageInstance) {
                        if( isset($imageInstance['width']) && $imageInstance['width'] == $width )
                        {
                            return $imageInstance['url'];
                        }
                    }
                }

                return null;
                
            }

            return $image['url'];
        }
        return null;
    }

    /**
     * @param $attributes
     *
     * @return null|string
     */
    public function srcset()
    {
        if ($images = $this->transformed) {
            if( !isset($images[0]) ) {
                return null;
            }


            $widths = [];
            $result = '';
            foreach ($images as $image) {
                $keys  = array_keys($image);
                $width = $image['width'] ?: null;
                if ($width && !isset($widths[ $width ])) {
                    $withs[ $width ] = true;
                    $result          .= $image['url'] . ' ' . $width . 'w, ';
                }
            }
            $srcset   = substr($result, 0, strlen($result) - 2);
            return $srcset;
        }
        return null;
    }

    /**
     * @param $transforms
     *
     * @return null || mixed
     */
    public function transform($transforms = null, $defaultOptions = null)
    {
        if (!$transforms) {
            return null;
        }

        if( $defaultOptions ) {
            $this->defaultOptions = $defaultOptions;
        }

        if (isset($transforms[0])) {
            $images = [];
            foreach ($transforms as $transform) {
                $transform = is_array($transform) ? array_merge($this->defaultOptions, $transform) : $transform;
                $transform = $this->calculateTargetSizeFromRatio($transform);
                $url       = $this->buildTransform($this->documentId, $transform);
                $images[]  = array_merge($transform, ['url' => $url]);
            }
            $this->transformed = $images;

            return $images;
        }
        else {
            $transforms        = array_merge($this->defaultOptions, $transforms);
            $transforms        = $this->calculateTargetSizeFromRatio($transforms);
            $url               = $this->buildTransform($this->documentId, $transforms);
            $image             = array_merge($transforms, ['url' => $url]);
            $this->transformed = $image;

            return $image;
        }
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['imageData', 'array'],
            ['imageData', 'default', 'value' => []],
            ['transformed', 'array'],
            ['transformed', 'default', 'value' => []],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param $transform
     *
     * @return mixed
     */
    protected function calculateTargetSizeFromRatio($transform)
    {
        if (!isset($transform['ratio']) && isset($transform['width']) && isset($transform['height']))
        {
            return $transform;
        }

        if( is_int($transform) )
        {
            $transform = [
                'width' => (int)$transform
            ];
        }

        // Use default image ratio if no ratio is defined
        $ratio = isset($transform['ratio']) ? (float)$transform['ratio'] : $this->ratio();
        $w     = isset($transform['width']) ? $transform['width'] : null;
        $h     = isset($transform['height']) ? $transform['height'] : null;
        // If both sizes and ratio is specified, let ratio take control based on width
        if ($w and $h)
        {
            $transform['height'] = round($w / $ratio);
        }
        else
        {
            if ($w)
            {
                $transform['height'] = (int)round($w / $ratio);
            }
            elseif ($h)
            {
                $transform['width'] = (int)round($h * $ratio);
            }
            else
            {
                // TODO: log that neither w nor h is specified with ratio
                // no idea what to do, return
                return $transform;
            }
        }
        unset($transform['ratio']); // remove the ratio setting so that it doesn't gets processed in the URL
        return $transform;
    }


    /**
     * @param $filename
     * @param $transform
     *
     * @return string
     */
    private function buildTransform($documentId, $transform)
    {
        $transform = Imageshop::$plugin->image->getImageTransform($documentId, $transform['width'], $transform['height']);

        if( !$transform )
        {
            return null;
        }

        

        return $transform->CreatePermaLinkFromDocumentIdResponse->CreatePermaLinkFromDocumentIdResult;
    }
}
