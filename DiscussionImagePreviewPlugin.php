<?php

namespace RJPlugins;

use Gdn_Plugin;
use Gdn;

class DiscussionImagePreviewPlugin extends Gdn_Plugin {
    /**
     *  Run on startup to init sane config settings and db changes.
     *
     *  @return void.
     */
    public function setup() {
        $this->structure();
    }

    /**
     *  Create tables and/or new columns.
     *
     *  @return void.
     */
    public function structure() {
        /*
        Gdn::structure()->table('ConfigTrace')
            ->column('Name', 'varchar(255)', false)
            ->column('Default', 'text', false)
            ->column('Result', 'text', false)
            ->set();
        */
    }

    /**
     * Dashboard settings page.
     *
     * @param SettingsController $sender instance of the calling class.
     *
     * @return void.
     */
    public function settingsController_rjDiscussionImagePreview_create($sender) {
return;
        $sender->permission('Garden.Settings.Manage');
        $sender->setHighlightRoute('settings/plugins');

        $sender->setData('Title', Gdn::translate('Plugin Settings'));

        $configurationModule = new \ConfigurationModule($sender);

        $options = [
            'Plugins.RjDiscussionImagePreview.TextBox' => [
                'LabelCode' => 'Example TextBox',
                'Description' => 'Description for text box example',
                'Control' => 'TextBox',
                'Default' => '17',
                'Options' => ['type' => 'integer']
            ],
            'Plugins.RjDiscussionImagePreview.DropDown' => [
                'LabelCode' => 'Example DropDown',
                'Description' => 'Description for drop down example',
                'Control' => 'DropDown',
                'Items' => [
                    '0' => 'zero',
                    '1' => 'one'
                ],
                'Default' => '1'
            ],
            'Plugins.RjDiscussionImagePreview.RadioList' => [
                'LabelCode' => 'Example RadioList',
                'Description' => 'Description for radio list example',
                'Control' => 'RadioList',
                'Items' => [
                    '0' => 'zero',
                    '1' => 'one'
                ],
                'Options' => ['display' => 'after'],
                'Default' => '1'
            ],
            'Plugins.RjDiscussionImagePreview.CategoryDropDown' => [
                'LabelCode' => 'Example CategoryDropDown',
                'Description' => 'Description for category drop down example',
                'Control' => 'CategoryDropDown',
                'Options' => ['IncludeNull' => true],
                'Default' => '2'
            ],
            'Plugins.RjDiscussionImagePreview.CheckBox' => [
                'LabelCode' => 'Example CheckBox',
                'Description' => 'Description for checkbox example',
                'Control' => 'CheckBox',
                'Default' => true
            ],
            'Plugins.RjDiscussionImagePreview.Toggle' => [
                'LabelCode' => 'Example Toggle',
                'Description' => 'Description for toggle example',
                'Control' => 'Toggle',
                'Default' => true
            ],
            'Plugins.RjDiscussionImagePreview.CheckBoxList' => [
                'LabelCode' => 'Example CheckBoxList',
                'Description' => 'Description for checkboxlist example',
                'Control' => 'CheckBoxList',
                'Items' => ['Groucho', 'Chico', 'Harpo'],
                'Default' => '1'
            ],
            'Plugins.RjDiscussionImagePreview.ImageUpload' => [
                'LabelCode' => 'Example ImageUpload',
                'Description' => 'Description for imageupload example',
                'Control' => 'ImageUpload'
            ]
        ];

        $configurationModule->initialize($options);

        if (Gdn::request()->isAuthenticatedPostBack()) {
            // Loop through all settings and save arrays directly to config.
            $formValues = $configurationModule->form()->formValues();
            foreach($formValues as $key => $value) {
                if (substr($key, 0, 8) !== 'Plugins.' || !is_array($value)) {
                    continue;
                }
                Gdn::config()->saveToConfig($key, array_values($value));
            }
        }
        $configurationModule->renderAll();
    }

    public function discussionsController_afterDiscussionContent_handler($sender, $args) {
        // $image = $this->fetchImage($args['Discussion']->DiscussionID);
        // decho($image, 'Image');
    }

    /**
     * Search for an image based on the discussion ID
     *
     * In the first version of the plugin, only use images which can be
     *   identified in the media table
     * Future versions should
     *   - be able to show images from discussion & comments
     *   - show random images from either only discussion or all posts
     * 
     * 
     * @param  [type] $discussionID [description]
     * @return MediaObject The image information connected to the discussion ID
     */
    public function fetchImage(int $discussionID) {
        // Try to fetch image from Attribute.
        $imageIDs = $args['Discussion']->Attributes['DiscussionImagePreview'] ?? null;
        // If the key in the attributes has been set to "false" explicitly, all
        // steps below have already been done and there is nothing left to do.
        if ($imageIDs === false) {
            // No images at all, sorry...
            return false;
        }

        $mediaModel = new \MediaModel('Media');
        // If Attributes field contains information, return what has been
        // parsed before.
        if ($imageIDs != null) {
            return $mediaModel->getID($imageIDs[0]);
        }

        // Try to fetch image from MediaTable.
        $medias = $mediaModel->getWhere(
            [
                'ForeignTable' => 'discussion',
                'ForeignID' => $discussionID
            ]
        );
        // Save IDs of images to discussion
        $imageIDs = [];
        $image = false;
        foreach ($medias as $media) {
            if (substr($media->Type, 0, 6) === 'image/') {
                // Gather all image IDs.
                $imageIDs[] = $media->MediaID;
                // Memorize first image media, because we will return that.
                if (!$image) {
                    $image = $media;
                }
            }
        }

        if ($image) {
            \DiscussionModel::instance()->saveToSerializedColumn(
                'Attributes',
                $discussionID,
                'DiscussionImagePreview',
                dbencode('imageIDs')
            );
        }
        return $image;        
    }

    public function discussionController_render_before($sender, $args) {
        $model = new DiscussionImageModel($sender->Discussion);
        $model->getByDiscussion('first');
    }
}
