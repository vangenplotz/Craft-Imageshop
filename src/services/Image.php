<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Integrate with an Imageshop account and use Imageshop resources in Craft
 *
 * @link      https://vangenplotz.no/
 * @copyright Copyright (c) 2018 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\services;

use vangenplotz\imageshop\Imageshop;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\FileHelper;
use vangenplotz\imageshop\models\ImageModel;
use vangenplotz\imageshop\records\ImageshopAsset;

/**
 * Image Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     0.0.1
 */
class Image extends Component
{
    
    // Protected Properties
    // =========================================================================

    protected $imageFileExtensions = array(
        IMAGETYPE_GIF => "gif",
        IMAGETYPE_JPEG => "jpg",
        IMAGETYPE_PNG => "png",
        IMAGETYPE_SWF => "swf",
        IMAGETYPE_PSD => "psd",
        IMAGETYPE_BMP => "bmp",
        IMAGETYPE_TIFF_II => "tiff",
        IMAGETYPE_TIFF_MM => "tiff",
        IMAGETYPE_JPC => "jpc",
        IMAGETYPE_JP2 => "jp2",
        IMAGETYPE_JPX => "jpx",
        IMAGETYPE_JB2 => "jb2",
        IMAGETYPE_SWC => "swc",
        IMAGETYPE_IFF => "iff",
        IMAGETYPE_WBMP => "wbmp",
        IMAGETYPE_XBM => "xbm",
        IMAGETYPE_ICO => "ico"
    );

    // Public Methods
    // =========================================================================

    /**
     * Saves an imageshop image and assigns it to a field
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->saveImageRelations($element, $folderId, $fieldId, $imageShopImages)
     *
     * @return void
     */

    public function saveImageRelations($elementId, $folderId, $fieldId, $imageShopImages = [])
    {
        $element = Craft::$app->elements->getElementById($elementId);
        $field = Craft::$app->fields->getFieldById($fieldId);
        $tempFolder = \craft\helpers\Assets::tempFilePath();
        
        $assetArray = [];

        foreach ($imageShopImages as $image)
        {
            $imageshopImageInstance = $this->transformImage($image);

            // Check if the image has already been imported
            $imageshopAsset = ImageshopAsset::find()
                ->where([
                    'imageshopDocumentInterface' => $imageshopImageInstance->documentInterface,
                    'imageshopDocumentLanguage' => $imageshopImageInstance->documentLanguage,
                    'imageshopDocumentId' => $imageshopImageInstance->documentId
                ])
                ->one();

            if(!$imageshopAsset && $asset = $this->createAssetFromImageshopImage($imageshopImageInstance))
            {
                $assetArray[] = $asset->id;

                /*$fileExtension = $this->imageFileExtensions[IMAGETYPE_JPEG];
                if (($image_type = exif_imagetype($imageshopImageInstance->url)) && (array_key_exists($image_type, $this->imageFileExtensions)))
                {
                    $fileExtension = $this->imageFileExtensions[$image_type];
                }

                $fileName = \craft\helpers\StringHelper::slugify($imageshopImageInstance->title) . "." . $fileExtension;
                $tempPath = $tempFolder . $fileName;
                file_put_contents($tempPath, fopen($imageshopImageInstance->url, 'r'));

                $assets = Craft::$app->getAssets();
                $folder = $assets->findFolder(['id' => $folderId]);

                $asset = new Asset();
                $asset->tempFilePath = $tempPath;
                $asset->filename = $fileName;
                $asset->title = $imageshopImageInstance->title;
                $asset->newFolderId = $folder->id;
                $asset->volumeId = $folder->volumeId;
                $asset->avoidFilenameConflicts = true;
                $asset->setScenario(Asset::SCENARIO_CREATE);

                $result = Craft::$app->elements->saveElement($asset);
    
                if($asset && $result) {
                    $imageshopAsset = new ImageshopAsset;
                    $imageshopAsset->assetId = $asset->id;
                    $imageshopAsset->imageshopDocumentInterface = $imageshopImageInstance->documentInterface;
                    $imageshopAsset->imageshopDocumentLanguage = $imageshopImageInstance->documentLanguage;
                    $imageshopAsset->imageshopDocumentId = (int)$imageshopImageInstance->documentId;
                    $imageshopAsset->imageshopTitle = $imageshopImageInstance->title;
                    $imageshopAsset->imageshopDescription = $imageshopImageInstance->alt;
                    $imageshopAsset->imageshopCredits = $imageshopImageInstance->credits;
                    $imageshopAsset->imageshopRights = $imageshopImageInstance->rights;
                    $imageshopAsset->imageshopUrl = $imageshopImageInstance->url;
                    $imageshopAsset->imageshopWidth = (int)$imageshopImageInstance->originalWidth;
                    $imageshopAsset->imageshopHeight = (int)$imageshopImageInstance->originalHeight;
                    $imageshopAsset->save();
                
                    $assetArray[] = $asset->id;
                }*/
            } elseif($imageshopAsset)
            {
                $assetArray[] = Asset::findOne($imageshopAsset->assetId)->id;
            }

        }

        if($field && $element && count($assetArray) > 0) {
            Craft::$app->relations->saveRelations($field, $element, $assetArray);
        }

    }

    /**
     * Create and return asset from Imagesop ImageModel
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->createAssetFromImageshopImage(ImageModel $imageshopImage)
     *
     * @return Asset|null
     */
    public function createAssetFromImageshopImage(ImageModel $imageshopImage)
    {
        // Set default file extension to jpeg
        $fileExtension = $this->imageFileExtensions[IMAGETYPE_JPEG];
        // Check image url to correct extension if available
        if (($image_type = exif_imagetype($imageshopImageInstance->url)) && (array_key_exists($image_type, $this->imageFileExtensions)))
        {
            $fileExtension = $this->imageFileExtensions[$image_type];
        }

        // Create kebab case file name based on title and append extension
        $fileName = \craft\helpers\StringHelper::slugify($imageshopImageInstance->title) . "." . $fileExtension;
        $tempPath = AssetsHelper::tempFilePath($fileName);
        FileHelper::writeToFile($tempPath, fopen($imageshopImageInstance->url, 'r'));
        //file_put_contents($tempPath, fopen($imageshopImageInstance->url, 'r'));

        $assets = Craft::$app->getAssets();
        $uploadFolder = Imageshop::$plugin->settings->uploadFolder;
        $folder = $assets->findFolder(['id' => $uploadFolder]);

        if(!$folder)
        {
            if($uploadFolder < 0)
            {
                Craft::warning('You need to define an upload folder in the plugin settings before you can upload images from Imageshop');
            } else {
                Craft::warning('Folder with id: ' . $uploadFolder . " does not exist. Pleas choose a differen upload folder in the plugin settings.");
            }
        }

        // Create asset from uploaded temp file
        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->filename = $fileName;
        $asset->title = $imageshopImageInstance->title;
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        $result = Craft::$app->elements->saveElement($asset);

        if($asset && $result) {
            // Create imageshop asset to keep track of asset relation to the image shop image
            $imageshopAsset = new ImageshopAsset;
            $imageshopAsset->assetId = $asset->id;
            $imageshopAsset->imageshopDocumentInterface = $imageshopImageInstance->documentInterface;
            $imageshopAsset->imageshopDocumentLanguage = $imageshopImageInstance->documentLanguage;
            $imageshopAsset->imageshopDocumentId = (int)$imageshopImageInstance->documentId;
            $imageshopAsset->imageshopTitle = $imageshopImageInstance->title;
            $imageshopAsset->imageshopDescription = $imageshopImageInstance->alt;
            $imageshopAsset->imageshopCredits = $imageshopImageInstance->credits;
            $imageshopAsset->imageshopRights = $imageshopImageInstance->rights;
            $imageshopAsset->imageshopUrl = $imageshopImageInstance->url;
            $imageshopAsset->imageshopWidth = (int)$imageshopImageInstance->originalWidth;
            $imageshopAsset->imageshopHeight = (int)$imageshopImageInstance->originalHeight;
            $imageshopAsset->save();
        
            $assetArray[] = $asset->id;

            return $asset;
        }
        
        return null;
    }


    /**
     * Transform the image, and return an image model
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->transformImage($documentId, $transforms)
     *
     * @return ImageModel
     */
    public function transformImage($document = null, $transforms = [], $defaultOptions = [])
    {
        if( !$document )
        {
            return null;
        }

        $documentComponents = explode('_', $document);

        if( count($documentComponents) < 3 )
        {
            return null;
        }
        
        $documentInterface = $documentComponents[0];
        $documentLanguage = $documentComponents[1];
        $documentId = $documentComponents[2];


        return new ImageModel($documentId, $documentInterface, $documentLanguage, $transforms, $defaultOptions);
    }

    /**
     * Get the image data for the document Id
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->getImageData($documentId)
     *
     * @return simpleXML
     */
    public function getImageData($documentId = null)
    {
        if( !$documentId )
        {
            return null;
        }

        $imageData = Imageshop::$plugin->soap->getImageData($documentId);

        if( !$imageData || property_exists($imageData, 'GetDocumentByIdResponse') == false )
        {
            return null;
        }

        return $imageData->GetDocumentByIdResponse->GetDocumentByIdResult ?? null;

    }

    /**
     * Get image transform permalink with document id, width and height
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->getImageTransform($documentId, $width, $height)
     *
     * @return simpleXML
     */
    public function getImageTransform($documentId = null, $width = null, $height = null)
    {

        if( !$documentId || !$width || !$height ) {
            return null;
        }

        return Imageshop::$plugin->soap->getImagePermalink($documentId, $width, $height);

    }

    /**
     * Serialize ImageshopModelArray
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->image->serialize($ImageshopModelArray)
     *
     * @return string
     */
    public function serialize($assets)
    {
        $assetIds = array_map(function($asset) {
            return $asset->id;
        }, $assets->all());

        if(count($assetIds))
        {
            $imageshopAssetStrings = [];

            // Fetch individual assets to keep order from asset array
            foreach ($assets->all() as $asset) {
                $imageshopAsset = ImageshopAsset::find()
                    ->where(["assetId" => $asset->id])
                    ->one();
                $imageshopAssetStrings[] = $imageshopAsset->imageshopDocumentInterface . "_" . $imageshopAsset->imageshopDocumentLanguage . "_" . $imageshopAsset->imageshopDocumentId;
            }
            
            return implode(",", $imageshopAssetStrings);
        }
        
        return "";
    }
}
