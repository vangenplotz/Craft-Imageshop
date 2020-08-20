<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Vangen & Plotz AS
 *
 * @link      vangenplotz.no
 * @copyright Copyright (c) 2020 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\console\controllers;

use vangenplotz\imageshop\Imageshop;

use Craft;
use craft\base\BaseRelationField;
use craft\base\ElementInterface;
use craft\elements\Asset;
use craft\models\FolderCriteria;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Default Command
 *
 * The first line of this class docblock is displayed as the description
 * of the Console Command in ./craft help
 *
 * Craft can be invoked via commandline console by using the `./craft` command
 * from the project root.
 *
 * Console Commands are just controllers that are invoked to handle console
 * actions. The segment routing is plugin-name/controller-name/action-name
 *
 * The actionIndex() method is what is executed if no sub-commands are supplied, e.g.:
 *
 * ./craft imageshop/default
 *
 * Actions must be in 'kebab-case' so actionDoSomething() maps to 'do-something',
 * and would be invoked via:
 *
 * ./craft imageshop/default/do-something
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     2.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Migrate old Imageshop field data to new asset relation
     *
     * @return mixed
     */
    public function actionIndex()
    {

        // Start user input section
        $this->stdout("As part of the migration, all images currently hosted on Imageshop will be moved to a Craft Volume.\n\n");

        // Get available Volumes
        $volumes = Craft::$app->Volumes->viewableVolumes;
        $volumeChoices = [];

        foreach ($volumes as $volume) {
            $volumeChoices[$volume["name"]] = $volume["id"];
        }

        $selectedVolumeOption = $this->select("Please select the volume you wish to migrate the images to", $volumeChoices);
        $selectedVolumeId = $volumeChoices[$selectedVolumeOption];
        
        // Choose folder
        $folderCriteria = new FolderCriteria(["volumeId" => $selectedVolumeId]);
        $folders = Craft::$app->assets->findFolders($folderCriteria);

        $folderOptions = [];

        foreach ($folders as $folder) {
            $folderOptions[$folder["name"]] = $folder["id"];
        }

        $selectedFolderOption = $this->select("Please select a folder to which the images will be saved", $folderOptions);
        $selectedFolderId = $folderOptions[$selectedFolderOption];



        // Get all fields that might contain Imageshop content
        $fields = (new \craft\db\Query())
            ->select(["id", "name", "handle"])
            ->from("fields")
            ->where(["type" => "vangenplotz\\imageshop\\fields\\ImageshopImage"])
            ->all();

        foreach ($fields as $field) {
            // Get all affected layouts
            $fieldLayouts = (new \craft\db\Query())
                ->select(["layoutId"])
                ->from("fieldlayoutfields")
                ->where(["fieldId" => $field['id']])
                ->all();

            $fieldLayoutIds = array_map(function($array) {
                return $array["layoutId"];
            }, $fieldLayouts);
            
            // Get all affected elements
            $elements = (new \craft\db\Query())
                ->select(["id"])
                ->from("elements")
                ->where("fieldLayoutId IN (" . implode(', ', $fieldLayoutIds) . ")")
                ->andWhere(["not", ["type" => "craft\\elements\\MatrixBlock"]])
                ->andWhere(["dateDeleted" => null])
                ->all();

            $elementIds = array_map(function($array) {
                return $array["id"];
            }, $elements);

            if(count($elementIds))
            {
                $this->stdout("\nStarting migration of field: " . $field['name'] . ".\n");

                // Get affected content
                $fieldName = "field_" . $field["handle"];
                $contents = (new \craft\db\Query())
                    ->select(["id", "elementId", $fieldName])
                    ->from("content")
                    ->where("elementId IN (" . implode(', ', $elementIds) . ")")
                    ->andWhere(["not", [$fieldName => null]])
                    ->andWhere(["not", [$fieldName => ""]])
                    ->all();
                
                $contentLength = count($contents);

                Console::startProgress(0, $contentLength);
                foreach ($contents as $index => $content)
                {
                    $images = explode(",", $content[$fieldName]);
                    Imageshop::$plugin->image->saveImageRelations($content["elementId"], $selectedFolderId, $field['id'], $images);
                    Console::updateProgress($index, $contentLength);
                }
                Console::endProgress("Completed " . $field['name'] . PHP_EOL);
            }

            // Migrate matrix content
            // Get matrix block type
            $matrixBlockTypes = (new \craft\db\Query())
                ->select(["fieldLayoutId", "handle"])
                ->from("matrixblocktypes")
                ->where("fieldLayoutId IN (" . implode(', ', $fieldLayoutIds) . ")")
                ->all();

            foreach ($matrixBlockTypes as $matrixBlockType)
            {
                $matrixElements = (new \craft\db\Query())
                    ->select(["id"])
                    ->from("elements")
                    ->where(["fieldLayoutId" => $matrixBlockType["fieldLayoutId"]])
                    ->andWhere(["type" => "craft\\elements\\MatrixBlock"])
                    ->andWhere(["dateDeleted" => null])
                    ->all();

                $matrixElementIds = array_map(function($array) {
                    return $array["id"];
                }, $matrixElements);

                if(count($matrixElementIds))
                {
                    $this->stdout("\nStarting migration of matrix field: " . $field['name'] . ".\n");

                    $fieldName = "field_" . $matrixBlockType["handle"] . "_" . $field["handle"];
                    $matrixContents = (new \craft\db\Query())
                        ->select(["id", "elementId", $fieldName])
                        ->from("matrixcontent_matrix")
                        ->where("elementId IN (" . implode(', ', $matrixElementIds) . ")")
                        ->andWhere(["not", [$fieldName => null]])
                        ->andWhere(["not", [$fieldName => ""]])
                        ->all();

                    $matrixContentLength = count($matrixContents);

                    Console::startProgress(0, $matrixContentLength);
                    foreach ($matrixContents as $index => $matrixContent)
                    {
                        $images = explode(",", $matrixContent[$fieldName]);
                        Imageshop::$plugin->image->saveImageRelations($matrixContent["elementId"], $selectedFolderId, $field['id'], $images);
                        Console::updateProgress($index, $matrixContentLength);
                    }
                    Console::endProgress("Completed " . $field['name'] . PHP_EOL);
                }

            }


        }


        /*$tempFolder = \craft\helpers\Assets::tempFilePath();
        $fileUrl = $imageshopImageInstance->url;

        $mimes = array(
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
            IMAGETYPE_ICO => "ico");

        $fileExtension = $mimes[IMAGETYPE_JPEG];
        if (($image_type = exif_imagetype($fileUrl)) && (array_key_exists($image_type ,$mimes))) {
            $fileExtension = $mimes[$image_type];
        }

        $fileName = \craft\helpers\StringHelper::slugify($imageshopImageInstance->title) . "." . $fileExtension;
        $tempPath = $tempFolder . $fileName;
        file_put_contents($tempPath, fopen($imageshopImageInstance->url, 'r'));

        $assets = Craft::$app->getAssets();
        $folder = $assets->findFolder(['id' => $selectedFolderId]);

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->filename = $fileName;
        $asset->title = $imageshopImageInstance->title;
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        $result = Craft::$app->elements->saveElement($asset);

        $element = Craft::$app->elements->getElementById($content["elementId"]);
        $field = Craft::$app->fields->getFieldById($fieldId);

        if($result && $element && $field) {
            $assetArray = [];
            $assetArray[] = $asset->id;
            Craft::$app->relations->saveRelations($field, $element, $assetArray);
        }

        var_dump($result);
        var_dump($asset->id);*/


        /*foreach ($contents as $content) {
            $images = explode(",", $content["field_imageshopImage"]);

            foreach ($images as $image) {
                $imageshopImageInstance = Imageshop::$plugin->image->transformImage($image);
                var_dump($imageshopImageInstance->url);
            }
        }*/

        //var_dump($content);

        return $fields;
    }

    /**
     * Handle imageshop/default/do-something console commands
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'something';

        echo "Welcome to the console DefaultController actionDoSomething() method\n";

        return $result;
    }
}
