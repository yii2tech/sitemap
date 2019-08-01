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
        'tag' => 'urlset',
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
    ];

    /**
     * @var array default options for {@see writeUrl()}.
     */
    public $defaultOptions = [];


    /**
     * Writes the URL block into the file.
     * @param string|array $url page URL or params.
     * @param array $options options list, valid options are:
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
     * @return int the number of bytes written.
     */
    public function writeUrl($url, array $options = [])
    {
        $this->incrementEntriesCount();

        if (!is_string($url)) {
            $url = $this->getUrlManager()->createAbsoluteUrl($url);
        }

        $xmlCode = '<url>';
        $xmlCode .= "<loc>{$url}</loc>";

        if (($unrecognizedOptions = array_diff(array_keys($options), ['lastModified', 'changeFrequency', 'priority'])) !== []) {
            throw new InvalidArgumentException('Unrecognized options: ' . implode(', ', $unrecognizedOptions));
        }

        $options = array_merge($this->defaultOptions, $options);

        if (isset($options['lastModified']) && ctype_digit($options['lastModified'])) {
            $options['lastModified'] = date('Y-m-d', $options['lastModified']);
        }

        $xmlCode .= isset($options['lastModified']) ? "<lastmod>{$options['lastModified']}</lastmod>" : '';
        $xmlCode .= isset($options['changeFrequency']) ? "<changefreq>{$options['changeFrequency']}</changefreq>" : '';
        $xmlCode .= isset($options['priority']) ? "<priority>{$options['priority']}</priority>" : '';

        $xmlCode .= '</url>';

        return $this->write($xmlCode);
    }
}
