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
 * $siteMapFile->writeUrl('http://mydomain.com/mycontroller/myaction', '2012-06-28', 'daily', '0.7');
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
     * This methods is invoked after the file is actually opened for writing.
     */
    protected function afterOpen()
    {
        parent::afterOpen();
        $this->write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    }

    /**
     * This method is invoked before the file is actually closed.
     */
    protected function beforeClose()
    {
        $this->write('</urlset>');
        parent::beforeClose();
    }

    /**
     * Writes the URL block into the file.
     * @param string $url page URL.
     * @param string|null $lastModifiedDate last modified date in format Y-m-d,
     * if null given the current date will be used.
     * @param string|null $changeFrequency page change frequency, the following values can be passed:
     * <ul>
     * <li>always</li>
     * <li>hourly</li>
     * <li>daily</li>
     * <li>weekly</li>
     * <li>monthly</li>
     * <li>yearly</li>
     * <li>never</li>
     * </ul>
     * @param null $priority URL search priority, by default '0.5' will be used
     * @return integer the number of bytes written.
     */
    public function writeUrl($url, $lastModifiedDate = null, $changeFrequency = null, $priority = null)
    {
        $this->incrementEntriesCount();
        $xmlCode = '<url>';
        $xmlCode .= "<loc>{$url}</loc>";
        if ($lastModifiedDate === null) {
            $lastModifiedDate = date('Y-m-d');
        }
        $xmlCode .= "<lastmod>{$lastModifiedDate}</lastmod>";
        if ($changeFrequency === null) {
            $changeFrequency = self::CHECK_FREQUENCY_DAILY;
        }
        $xmlCode .= "<changefreq>{$changeFrequency}</changefreq>";
        if (empty($priority)) {
            $priority = '0.5';
        }
        $xmlCode .= "<priority>{$priority}</priority>";
        $xmlCode .= '</url>';
        return $this->write($xmlCode);
    }
}
