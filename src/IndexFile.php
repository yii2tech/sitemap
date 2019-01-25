<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

use Yii;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
 * IndexFile is a helper to create the site map index XML files.
 * This class allows to create an XML file, filling it with the links to files,
 * found in given path.
 * Example:
 *
 * ```php
 * use yii2tech\sitemap\IndexFile;
 *
 * $siteMapIndexFile = new IndexFile();
 * $siteMapIndexFile->writeUpFromPath('@app/web/sitemap');
 * ```
 *
 * If source site map files and an index file are in the same directory, you may use [[writeUp()]].
 *
 * @see BaseFile
 * @see File
 * @see http://www.sitemaps.org/
 *
 * @property string $fileBaseUrl base URL for the directory, which contains the site map files.
 * If not set URL to 'sitemap' folder under current web root will be used.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class IndexFile extends BaseFile
{
    /**
     * @var string name of the site map file.
     */
    public $fileName = 'sitemap_index.xml';
    /**
     * @var string base URL for the directory, which contains the site map files.
     */
    private $_fileBaseUrl = '';

    public $xmlOpenTag = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    public $xmlCloseTag = '</sitemapindex>';


    /**
     * @param string $fileBaseUrl base URL for the directory, which contains the site map files.
     * Path alias can be used here.
     */
    public function setFileBaseUrl($fileBaseUrl)
    {
        $this->_fileBaseUrl = Yii::getAlias($fileBaseUrl);
    }

    /**
     * @return string base URL for the directory, which contains the site map files.
     */
    public function getFileBaseUrl()
    {
        if (empty($this->_fileBaseUrl)) {
            $this->_fileBaseUrl = $this->defaultFileBaseUrl();
        }
        return $this->_fileBaseUrl;
    }

    /**
     * Initializes the [[fileBaseUrl]] value.
     * @return string default file base URL.
     */
    protected function defaultFileBaseUrl()
    {
        $urlManager = $this->getUrlManager();
        return $urlManager->getHostInfo() . $urlManager->getBaseUrl() . '/sitemap';
    }

    /**
     * {@inheritdoc}
     */
    protected function afterOpen()
    {
        parent::afterOpen();
        $this->write($this->xmlOpenTag);
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeClose()
    {
        $this->write($this->xmlCloseTag);
        parent::beforeClose();
    }

    /**
     * Writes the site map block into the file.
     * @param string $siteMapFileUrl site map file URL.
     * @param string|int|null $lastModifiedDate last modified timestamp or date in format Y-m-d,
     * if null given the current date will be used.
     * @return int the number of bytes written.
     */
    public function writeSiteMap($siteMapFileUrl, $lastModifiedDate = null)
    {
        $this->incrementEntriesCount();

        $xmlCode = '<sitemap>';
        $xmlCode .= "<loc>{$siteMapFileUrl}</loc>";

        if ($lastModifiedDate !== null) {
            if (ctype_digit($lastModifiedDate)) {
                $lastModifiedDate = date('Y-m-d', $lastModifiedDate);
            }
            $xmlCode .= "<lastmod>{$lastModifiedDate}</lastmod>";
        }

        $xmlCode .= '</sitemap>';

        return $this->write($xmlCode);
    }

    /**
     * Fills up the index file from the files found in given path.
     * @throws Exception on failure.
     * @param string $path file path, which contains the site map files.
     * @return int amount of site maps written.
     */
    public function writeUpFromPath($path)
    {
        $path = Yii::getAlias($path);

        $findOptions = [
            'only' => [
                '*.xml',
                '*.gzip'
            ],
        ];
        $files = FileHelper::findFiles($path, $findOptions);
        if (!is_array($files) || empty($files)) {
            throw new Exception('Unable to find site map files under the path "' . $path . '"');
        }

        $siteMapsCount = 0;
        $fileBaseUrl = rtrim($this->getFileBaseUrl(), '/');
        $indexFileName = $this->getFullFileName();
        foreach ($files as $file) {
            if ($file === $indexFileName) {
                continue;
            }
            $fileUrl = $fileBaseUrl . '/' . basename($file);
            $lastModifiedDate = date('Y-m-d', filemtime($file));
            $this->writeSiteMap($fileUrl, $lastModifiedDate);
            $siteMapsCount++;
        }

        $this->close();

        return $siteMapsCount;
    }

    /**
     * Fills up the index file from the files found in own file path.
     * @return int amount of site maps written.
     */
    public function writeUp()
    {
        return $this->writeUpFromPath($this->fileBasePath);
    }
}
