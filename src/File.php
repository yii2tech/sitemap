<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

use yii\base\InvalidArgumentException;

/**
 * File is a helper to create the site map XML files.
 * Example:
 *
 * ```php
 * use yii2tech\sitemap\File;
 *
 * $siteMapFile = new File();
 * $siteMapFile->writeUrl(['site/index']);
 * $siteMapFile->writeUrl(['site/contact'], ['priority' => '0.4']);
 * $siteMapFile->writeUrl('http://mydomain.com/mycontroller/myaction', [
 *     'lastModified' => '2012-06-28',
 *     'changeFrequency' => 'daily',
 *     'priority' => '0.7'
 * ]);
 * ...
 * $siteMapFile->close();
 * ```
 *
 * @see BaseFile
 * @see http://www.sitemaps.org/
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class File extends BaseFile
{
    // Check frequency constants:
    const CHECK_FREQUENCY_ALWAYS = 'always';
    const CHECK_FREQUENCY_HOURLY = 'hourly';
    const CHECK_FREQUENCY_DAILY = 'daily';
    const CHECK_FREQUENCY_WEEKLY = 'weekly';
    const CHECK_FREQUENCY_MONTHLY = 'monthly';
    const CHECK_FREQUENCY_YEARLY = 'yearly';
    const CHECK_FREQUENCY_NEVER = 'never';

    /**
     * {@inheritdoc}
     */
    public $rootTag = [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
    ];

    /**
     * @var array default options for {@see writeUrl()}.
     */
    public $defaultOptions = [];


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (!empty($this->rootTag) && !isset($this->rootTag['tag'])) {
            $this->rootTag['tag'] = 'urlset';
        }
    }

    /**
     * Writes the URL block into the file.
     * @param string|array $url page URL or params.
     * @param array $options options list, valid options are:
     *
     * - 'lastModified' - string|int, last modified date in format Y-m-d or timestamp.
     * - 'changeFrequency' - string, page change frequency, the following values can be passed:
     *
     *   * always
     *   * hourly
     *   * daily
     *   * weekly
     *   * monthly
     *   * yearly
     *   * never
     *
     *   You may use constants defined in this class here.
     * - 'priority' - string|float URL search priority in range 0..1
     * - 'images' - array list of images bound to the URL, {@see composeImage()} for details.
     * - 'videos' - array list of videos bound to the URL, {@see composeVideo()} for details.
     *
     * @param string|null $extraContent extra XML content to be placed inside 'url' tag.
     * @return int the number of bytes written.
     */
    public function writeUrl($url, array $options = [], $extraContent = null)
    {
        $this->incrementEntriesCount();

        if (!is_string($url)) {
            $url = $this->getUrlManager()->createAbsoluteUrl($url);
        }

        $xmlCode = '<url>';
        $xmlCode .= "<loc>{$url}</loc>";

        if (($unrecognizedOptions = array_diff(array_keys($options), ['lastModified', 'changeFrequency', 'priority', 'images', 'videos'])) !== []) {
            throw new InvalidArgumentException('Unrecognized options: ' . implode(', ', $unrecognizedOptions));
        }

        $options = array_merge($this->defaultOptions, $options);

        if (isset($options['lastModified']) && ctype_digit($options['lastModified'])) {
            $options['lastModified'] = date('Y-m-d', $options['lastModified']);
        }

        if (isset($options['lastModified'])) {
            $xmlCode .= '<lastmod>' . $this->normalizeDateValue($options['lastModified']) . '</lastmod>';
        }
        if (isset($options['changeFrequency'])) {
            $xmlCode .= '<changefreq>' . $options['changeFrequency'] . '</changefreq>';
        }
        if (isset($options['priority'])) {
            $xmlCode .= '<priority>' . $options['priority'] . '</priority>';
        }

        if (!empty($options['images'])) {
            foreach ($options['images'] as $image) {
                $xmlCode .= $this->lineBreak . $this->composeImage($image);
            }
        }

        if (!empty($options['videos'])) {
            foreach ($options['videos'] as $video) {
                $xmlCode .= $this->lineBreak . $this->composeVideo($video);
            }
        }

        if ($extraContent !== null) {
            $xmlCode .= $extraContent;
        }

        $xmlCode .= '</url>';

        return $this->write($xmlCode);
    }

    /**
     * Creates XML code for image tag.
     * @see https://www.google.com/schemas/sitemap-image/1.1/
     *
     * @param array $image image options, valid options are:
     *
     * - 'url' - string
     * - 'title' - string
     * - 'caption' - string
     * - 'geoLocation' - string
     * - 'license' - string
     *
     * @return string XML code.
     * @since 1.1.0
     */
    protected function composeImage(array $image)
    {
        $xmlCode = '<image:image>';

        $xmlCode .= '<image:loc>' . $image['url'] . '</image:loc>';

        if (isset($image['title'])) {
            $xmlCode .= '<image:title>' . $image['title'] . '</image:title>';
        }
        if (isset($image['caption'])) {
            $xmlCode .= '<image:caption>' . $image['caption'] . '</image:caption>';
        }
        if (isset($image['geoLocation'])) {
            $xmlCode .= '<image:geo_location>' . $image['geoLocation'] . '</image:geo_location>';
        }
        if (isset($image['license'])) {
            $xmlCode .= '<image:license>' . $image['license'] . '</image:license>';
        }

        $xmlCode .= '</image:image>';

        return $xmlCode;
    }

    /**
     * Creates XML code for video tag.
     * @see https://www.google.com/schemas/sitemap-video/1.1/
     *
     * @param array $video video options, valid options are:
     *
     * - 'thumbnailUrl' - string, URL to the thumbnail
     * - 'title' - string, video page title
     * - 'description' - string, video page meta description
     * - 'contentUrl' - string
     * - 'duration' - int|string, video length in seconds
     * - 'expirationDate' - string|int
     * - 'rating' - string
     * - 'viewCount' - string|int
     * - 'publicationDate' - string|int
     * - 'familyFriendly' - string
     * - 'requiresSubscription' - string
     * - 'live' - string
     * - 'player' - array, options:
     *
     *   * 'url' - string, URL to raw video clip
     *   * 'allowEmbed' - bool|string
     *   * 'autoplay' - bool|string
     *
     * - 'restriction' - array, options:
     *
     *   * 'relationship' - string
     *   * 'restriction' - string
     *
     * - 'gallery' - array, options:
     *
     *   * 'title' - string
     *   * 'url' - string
     *
     * - 'price' - array, options:
     *
     *   * 'currency' - string
     *   * 'price' - string|float
     *
     * - 'uploader' - array, options:
     *
     *   * 'info' - string
     *   * 'uploader' - string
     *
     * @return string XML code.
     * @since 1.1.0
     */
    protected function composeVideo(array $video)
    {
        $xmlCode = '<video:video>';

        if (isset($video['thumbnailUrl'])) {
            $xmlCode .= '<video:thumbnail_loc>' . $video['thumbnailUrl'] . '</video:thumbnail_loc>';
        }
        if (isset($video['title'])) {
            $xmlCode .= '<video:title><![CDATA[' . $video['title'] . ']]></video:title>';
        }
        if (isset($video['description'])) {
            $xmlCode .= '<video:description><![CDATA[' . $video['description'] . ']]></video:description>';
        }
        if (isset($video['contentUrl'])) {
            $xmlCode .= '<video:content_loc>' . $video['contentUrl'] . '</video:content_loc>';
        }
        if (isset($video['duration'])) {
            $xmlCode .= '<video:duration>' . $video['duration'] . '</video:duration>';
        }
        if (isset($video['expirationDate'])) {
            $xmlCode .= '<video:expiration_date>' . $this->normalizeDateValue($video['expirationDate']) . '</video:expiration_date>';
        }
        if (isset($video['rating'])) {
            $xmlCode .= '<video:rating>' . $video['rating'] . '</video:rating>';
        }
        if (isset($video['viewCount'])) {
            $xmlCode .= '<video:view_count>' . $video['viewCount'] . '</video:view_count>';
        }
        if (isset($video['publicationDate'])) {
            $xmlCode .= '<video:publication_date>' . $this->normalizeDateValue($video['publicationDate']) . '</video:publication_date>';
        }
        if (isset($video['familyFriendly'])) {
            $xmlCode .= '<video:family_friendly>' . $video['familyFriendly'] . '</video:family_friendly>';
        }
        if (isset($video['requiresSubscription'])) {
            $xmlCode .= '<video:requires_subscription>' . $video['requiresSubscription'] . '</video:requires_subscription>';
        }
        if (isset($video['live'])) {
            $xmlCode .= '<video:live>' . $video['live'] . '</video:live>';
        }
        if (isset($video['player'])) {
            $xmlCode .= '<video:player_loc allow_embed="' . $this->normalizeBooleanValue($video['player']['allowEmbed']) . '" autoplay="' . $this->normalizeBooleanValue($video['player']['autoplay']) . '">'
                . $video['player']['url']
                . '</video:player_loc>';
        }
        if (isset($video['restriction'])) {
            $xmlCode .= '<video:restriction relationship="' . $video['restriction']['relationship'] . '">' . $video['restriction']['restriction'] . '</video:restriction>';
        }
        if (isset($video['gallery'])) {
            $xmlCode .= '<video:gallery_loc title="' . $video['gallery']['title'] . '">' . $video['gallery']['url'] . '</video:gallery_loc>';
        }
        if (isset($video['price'])) {
            $xmlCode .= '<video:price currency="' . $video['price']['currency'] . '">' . $video['price']['price'] . '</video:price>';
        }
        if (isset($video['uploader'])) {
            $xmlCode .= '<video:uploader info="' . $video['uploader']['info'] . '">' . $video['uploader']['uploader'] . '</video:uploader>';
        }

        $xmlCode .= '</video:video>';

        return $xmlCode;
    }
}
