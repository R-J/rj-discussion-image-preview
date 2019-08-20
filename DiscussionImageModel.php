<?php

namespace RJPlugins;

use Gdn;

class DiscussionImageModel extends \Gdn_Model {
    private $discussion;

    public function __construct(object $discussion) {
        $this->discussion = $discussion;
    }

    public function setDiscussion($discussion) {
        $this->discussion = $discussion;
        return $this->discussion;
    }

    public function getDiscussion() {
        return $this->discussion;
    }

    public function getByDiscussion(string $mode = 'first') {
        if ($mode != 'first') {
            $mode = 'random';
        }
        // $imageIDs = $this->getFromAttributes($mode);
        $this->getImagesFromParsedBody();
    }

    /**
     * Get the first or a random image from a discussions body.
     *
     * @param string $mode One of first|random
     *
     * @return int|bool Either the media ID or boolen
     */
    private function getImageIDsFromAttributes() {
        // Try to fetch image from Attribute.
        $imageIDs = $this->discussion->Attributes['DiscussionImagePreview'] ?? null;

        // Return FALSE if Attribute is not set
        if (!is_array($imageIDs)) {
            return false;
        }
        return $imageIDs;
    }

    private function getImagesFromMediaTable() {
        // Try to fetch image from MediaTable.
        return Gdn::sql()
            ->from('Media')
            ->where([
                'ForeignTable' => 'discussion',
                'ForeignID' => $this->discussion->DiscussionID
            ])
            ->like('Type', 'image/%')
            ->get()
            ->resultObject();
    }

    private function getImagesFromParsedBody() {
        $text = \Gdn_Format::to($this->discussion->Body, $this->discussion->Format);
        $url = Gdn::request()->url('/uploads/', true);
        // $pattern = preg_quote('<img src ="'.$url).'(.*?)"';
        $pattern = '%.*\<img src=\"'.preg_quote($url).'(.*?)".*%';
        decho('<!-- '.$pattern.' -->');
        preg_match_all(
            $pattern,
            $text,
            $matches
        );
        //$matches[1]
        decho($matches);
        /*
        DEBUG: Array
(
    [0] => Array
        (
            [0] => <p><img src="http://v30.muxi.de/uploads/editor/x1/fake3.jpg" alt="" title="" /><br />
            [1] => <img src="http://v30.muxi.de/uploads/editor/x1/y1sf9nqdq1hj.jpg" alt="" title="" /><br />
            [2] => <img src="http://v30.muxi.de/uploads/editor/x1/y1sf9nqdq1hj.jpg" alt="" title="" /><br />
            [3] => <img src="http://v30.muxi.de/uploads/editor/x1/fake1.jpg" alt="" title="" /><br />
            [4] => <img src="http://v30.muxi.de/uploads/editor/x1/fake2.jpg" alt="" title="" /></p>
        )

    [1] => Array
        (
            [0] => editor/x1/fake3.jpg
            [1] => editor/x1/y1sf9nqdq1hj.jpg
            [2] => editor/x1/y1sf9nqdq1hj.jpg
            [3] => editor/x1/fake1.jpg
            [4] => editor/x1/fake2.jpg
        )

         */
    }
}