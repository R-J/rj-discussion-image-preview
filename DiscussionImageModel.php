<?php

namespace RJPlugins;

use Gdn;

/**
 * Class to provide ways to extract images from a discussion.
 */
class DiscussionImageModel extends \Gdn_Model {
    /** @var object The discussion that should be inspected */
    private $discussion;

    /**
     * Sets the discussion on class instantiation.
     * 
     * @param object $discussion The discussion that should be inspected.
     */
    public function __construct(object $discussion) {
        $this->discussion = $discussion;
    }

    /**
     * Set the discussion.
     * 
     * @param object $discussion The discussion that should be inspected.
     * 
     * @return object $discussion The discussion that should be inspected.
     */
    public function setDiscussion($discussion) {
        $this->discussion = $discussion;
        return $this->discussion;
    }

    /**
     * Set the discussion.
     * 
     * @return object $discussion The discussion that should be inspected.
     */
    public function getDiscussion() {
        return $this->discussion;
    }

    /**
     * TODO!
     * Return Media object of a discussion.
     *
     * @return object|bool Either returns the Media object on success or FALSE
     */
    public function getByDiscussion(bool $createThumb = false) {
        $thumbNail = $this->getThumbFromAttributes();
        if ($thumbNail === '') {
            return false;
        }

        $media = $this->getMediaByForeignID();
decho($media);

        $media = $this->getMediaFromParsedBody();
decho($media);

    }

    /**
     * Look at the Discussion->Attributes[] for a thumb nail.
     *
     * If this method returns an empty string, the discussion has already been
     * parsed and there is no i,age included.
     *
     * @return string|bool Either the thumbs path or FALSE
     */
    private function getThumbFromAttributes() {
        // Try to fetch image from Attribute.
        return $this->discussion->Attributes['DiscussionImagePreview'] ?? false;
    }

    /**
     * Look for current discussion in media table and return first object.
     *
     * @return Gdn_Dataset The first Media item connected to the discussion
     */
    private function getMediaByForeignID() {
        // Try to fetch image from MediaTable.
        return Gdn::sql()
            ->select()
            ->from('Media')
            ->where([
                'ForeignTable' => 'discussion',
                'ForeignID' => $this->discussion->DiscussionID
            ])
            ->like('Type', 'image/%')
            ->get()
            ->firstRow();
    }

    /**
     * Parse formatted html body for img tags and look them up in the media table.
     *
     * @return Gdn_Dataset|bool The media object or false.
     */
    private function getMediaFromParsedBody() {
        $text = \Gdn_Format::to($this->discussion->Body, $this->discussion->Format);
        $url = Gdn::request()->url('/uploads/', true);
        $pattern = '%.*\<immmg src=\"'.preg_quote($url).'(.*?)".*%';
        // TODO: change to preg_match
        preg_match(
            $pattern,
            $text,
            $matches
        );
decho($matches);
        if (count($matches) < 2) {
            return false;
        }
        $path = $matches[1][0] ?? false;
        if ($path === false) {
            return false;
        }
decho($path);

        return Gdn::sql()
            ->select()
            ->from('Media')
            ->where('Path', $path)
            ->like('Type', 'image/%', 'right')
            ->get()
            ->firstRow();
    }
}