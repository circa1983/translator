<?php
/**
 * Translator plugin for Craft CMS 3.x
 *
 * A translation field for Craft CMS
 *
 * @link      https://www.thebasement.be/
 * @copyright Copyright (c) 2019 Jan D'Hollander
 */

namespace circa1983\translator\fields;

use circa1983\translator\Translator;
use circa1983\translator\assetbundles\translatorfield\TranslatorAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use yii\db\Schema;
use craft\helpers\Json;


/**
 * @author    Jan D'Hollander
 * @package   Translator
 * @since     0.1.0
 */
class TranslatorField extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $translator;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('translator', 'Translator');
    }

    // Public Methods
    // =========================================================================

    // public function rules()
    // {
    //     $rules = parent::rules();
    //     $rules = array_merge($rules, [
    //         ['someAttribute', 'string'],
    //         ['someAttribute', 'default', 'value' => 'Some Default'],
    //     ]);
    //     return $rules;
    // }

    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    public function normalizeValue($value, ElementInterface $element = null)
    {
        return $value;
    }

    public function serializeValue($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }

    public function getAllTemplates($dir, &$results = array()){
        $files = scandir($dir);
        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getAllTemplates($path, $results);
                $results[] = $path;
            }
        }
        return $results;
    }

    public function getTranslatablesFromTemplates(&$files){
        $translatables = array();

        foreach ($files as $value) {
            $html = file_get_contents($value);
            if (preg_match_all('/{{([^}]+)\|t(?:ranslate)?\b(?:\|([^}]+)|\s*)}}/', $html, $matches)) {
                foreach ($matches[1] as $item) {
                    $clean = substr(trim($item), 1, -1);
                    $translatables[$clean] = $clean;
                }
            }
        }
        return array_unique($translatables);
    }

    public function getTranslatables(){
        // TODO: add modules folder
        $dir = Craft::$app->path->getSiteTemplatesPath();
        $templates = $this->getAllTemplates($dir);

        $translatables = $this->getTranslatablesFromTemplates($templates);
        return $translatables;
    }

    public function getTranslationsFromFile(&$translations = array()){
        preg_match('%globals\/(.+)\/%', Craft::$app->request->pathInfo, $cp_global_locale);
        $cp_site_handle = Craft::$app->request->getParam('site') ?? $cp_global_locale[1] ?? Craft::$app->getSites()->getPrimarySite()->handle ?? '';
        $locale = Craft::$app->getSites()->getSiteByHandle($cp_site_handle)->language;

        $translationPath = Craft::$app->Path->getSiteTranslationsPath();
        $translationFile = $translationPath . '/' . $locale . '/site.php';

        if (file_exists($translationFile)){
            $translations = include $translationFile;
        }
        return $translations;
    }

    public function setStatusForEachElement(&$results = array()){

        $translationsFromFile = $this->getTranslationsFromFile();
        $translationsFromTemplate = $this->getTranslatables();


        foreach ($translationsFromTemplate as $item) {
            $translated = false;
            $translatedValue = '';

            if (isset($translationsFromFile[$item]) and $translationsFromFile[$item] != $item and $translationsFromFile[$item] != '')  {
                $translated = true;
                $translatedValue=$translationsFromFile[$item];
            }
            $results[] = ['original' => $item,'translation' => $translatedValue, 'translated' => $translated ];
        }
        return $results;
    }

    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'translator/_components/fields/settings',
            [
                'field' => $this,
                'options' => $this->getTranslatables(),
            ]
        );
    }

    public function getInputHtml($value, ElementInterface $element = null): string
    {
        Craft::$app->getView()->registerAssetBundle(TranslatorAsset::class);

        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);


        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'field' => $this,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').TranslatorTranslator(" . $jsonVars . ");");

        $translationsFromFileWithStatus = $this->setStatusForEachElement();
        $savedOptions = $this->translator;

        $options = array_filter($translationsFromFileWithStatus, function($elem) use ($savedOptions){
            return in_array($elem["original"], $savedOptions);
        });


        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'translator/_components/fields/input',
            [
                'id' => $id,
                'name' => $this->handle,
                'options' => $options,
                'value' => $value,
                'field' => $this,
                'prefix' => Craft::$app->getView()->namespaceInputId(''),
                'namespacedId' => $namespacedId,
            ]
        );
    }
}
