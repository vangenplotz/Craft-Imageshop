<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Imageshop plugin for Craft CMS 3.x
 *
 * @link      https://vangenplotz.no
 * @copyright Copyright (c) 2020 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\models;

use vangenplotz\imageshop\Imageshop;

use Craft;
use craft\base\Model;
use craft\elements\Asset;

/**
 * ImageshopAsset Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     2.0.0
 */
class ImageshopAsset extends Model
{

    // Public Methods
    // =========================================================================

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
            ['assetId', 'integer', 'required' => true],
            ['imageshopDocumentInterface', 'string', 'required' => true],
            ['imageshopDocumentLanguage', 'string', 'required' => true],
            ['imageshopDocumentId', 'integer', 'required' => true],
            ['imageshopTitle', 'string', 'required' => false],
            ['imageshopDescription', 'string', 'required' => false],
            ['imageshopCredits', 'string', 'required' => false],
            ['imageshopRights', 'string', 'required' => false],
            ['imageshopUrl', 'string', 'required' => false],
            ['imageshopWidth', 'integer', 'required' => false],
            ['imageshopHeight', 'integer', 'required' => false],
        ];
    }
}

