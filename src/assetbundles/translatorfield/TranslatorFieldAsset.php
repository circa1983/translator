<?php
/**
 * Translator plugin for Craft CMS 3.x
 *
 * A translation field for Craft CMS
 *
 * @link      https://www.thebasement.be/
 * @copyright Copyright (c) 2019 Jan D'Hollander
 */

namespace circa1983\translator\assetbundles\translatorfield;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Jan D'Hollander
 * @package   Translator
 * @since     0.1.0
 */
class TranslatorAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@circa1983/translator/assetbundles/translatorfield/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Translator.js',
            'js/Reorder.js',
        ];

        $this->css = [
            'css/Translator.css',
        ];

        parent::init();
    }
}
