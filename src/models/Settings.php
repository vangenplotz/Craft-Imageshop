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
 * Imageshop Settings Model
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
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Imageshop access token
     *
     * @var string
     */
    public $token = '';

    /**
     * Imageshop interface name
     *
     * @var string
     */
    public $interfaceName = '';


    /**
     * Imageshop language
     *
     * @var string
     */
    public $language = 'no';


    /**
     * Upload folder
     *
     * @var string
     */
    public $uploadFolder = -1;

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
            [['token', 'interfaceName', 'language'], 'required'],
            ['token', 'string'],
            ['interfaceName', 'string'],
            ['language', 'string'],
            ['uploadFolder', 'integer']
        ];
    }
}
