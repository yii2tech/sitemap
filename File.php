<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

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
     * @inheritdoc
     */
    protected function afterOpen()
    {
        parent::afterOpen();
        $this->write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    }

    /**
     * @inheritdoc
     */
    protected function beforeClose()
    {
        $this->write('</urlset>');
        parent::beforeClose();
    }

    /**
     * Writes the URL block into the file.
     * @param string|array $url page URL or params.
     * @param array $options options list, valid options are:
     * - 'lastModified' - string|integer, last modified date in format Y-m-d or timestamp.
     *   by default current date will be used.
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
     *   by default 'daily' will be used. You may use constants defined in this class here.
     * - 'priority' - string|float URL search priority in range 0..1, by default '0.5' will be used
     * @return integer the number of bytes written.
     */
    public function writeUrl($url, array $options = [])
    {
        $this->incrementEntriesCount();

        if (!is_string($url)) {
            $url = $this->getUrlManager()->createAbsoluteUrl($url);
        }

        $xmlCode = '<url>';
        $xmlCode .= "<loc>{$url}</loc>";

        $options = array_merge(
            [
                'lastModified' => date('Y-m-d'),
                'changeFrequency' => self::CHECK_FREQUENCY_DAILY,
                'priority' => '0.5',
            ],
            $options
        );
        if (ctype_digit($options['lastModified'])) {
            $options['lastModified'] = date('Y-m-d', $options['lastModified']);
        }

        $xmlCode .= "<lastmod>{$options['lastModified']}</lastmod>";
        $xmlCode .= "<changefreq>{$options['changeFrequency']}</changefreq>";
        $xmlCode .= "<priority>{$options['priority']}</priority>";

        $xmlCode .= '</url>';
        return $this->write($xmlCode);
    }
}
