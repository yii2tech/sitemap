<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

use Yii;
use yii\base\Exception;
use yii\base\BaseObject;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\web\UrlManager;

/**
 * BaseFile is a base class for the sitemap XML files.
 *
 * @see http://www.sitemaps.org/
 *
 * @property int $entriesCount the count of entries written into the file, this property is read-only.
 * @property bool $isEntriesLimitReached whether the max entries limit is already reached or not.
 * @property UrlManager|array|string $urlManager the URL manager object or the application component ID of the URL manager.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BaseFile extends BaseObject
{
    /**
     * @var int max allowed XML entries count.
     * @since 1.1.0
     */
    public $maxEntriesCount = 50000;
    /**
     * @var int max allowed files size in bytes.
     * By default - 50 MB.
     * @since 1.1.0
     */
    public $maxFileSize = 52428800;
    /**
     * @var string name of the site map file.
     */
    public $fileName = 'sitemap.xml';
    /**
     * @var int the chmod permission for directories and files,
     * created in the process. Defaults to 0777 (owner rwx, group rwx and others rwx).
     */
    public $filePermissions = 0777;
    /**
     * @var string directory, which should be used to store generated site map file.
     * By default '@app/web/sitemap' will be used.
     */
    public $fileBasePath = '@app/web/sitemap';
    /**
     * @var string content, which should be written at the beginning of the file, once it has been opened.
     * @since 1.1.0
     */
    public $header = '<?xml version="1.0" encoding="UTF-8"?>';
    /**
     * @var array defines XML root tag name and attributes.
     * Name of tag is defined by 'tag' key, any other keys are considered to be tag attributes.
     * For example:
     *
     * ```
     * [
     *     'tag' => 'urlset',
     *     'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
     * ]
     * ```
     *
     * @see Html::beginTag()
     * @see Html::endTag()
     *
     * @since 1.1.0
     */
    public $rootTag;
    /**
     * @var string content, which should be written at the end of the file before it is closed.
     * @since 1.1.0
     */
    public $footer = '';
    /**
     * @var resource file resource handler.
     */
    private $_fileHandler;
    /**
     * @var int the count of entries written into the file.
     */
    private $_entriesCount = 0;
    /**
     * @var UrlManager|array|string the URL manager object or the application component ID of the URL manager.
     */
    private $_urlManager = 'urlManager';


    /**
     * Destructor.
     * Makes sure the opened file is closed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @return int the count of entries written into the file.
     */
    public function getEntriesCount()
    {
        return $this->_entriesCount;
    }

    /**
     * @param UrlManager|array|string $urlManager URL manager to be used for URL creation.
     */
    public function setUrlManager($urlManager)
    {
        $this->_urlManager = $urlManager;
    }

    /**
     * @return UrlManager URL manager used for URL creation.
     */
    public function getUrlManager()
    {
        if (!is_object($this->_urlManager)) {
            $this->_urlManager = Instance::ensure($this->_urlManager, UrlManager::class);
        }

        return $this->_urlManager;
    }

    /**
     * @return bool whether the max entries limit is already reached or not.
     */
    public function getIsEntriesLimitReached()
    {
        return ($this->_entriesCount >= $this->maxEntriesCount);
    }

    /**
     * Increments the internal entries count.
     * @throws Exception if limit exceeded.
     * @return int new entries count value.
     */
    protected function incrementEntriesCount()
    {
        $this->_entriesCount++;
        if ($this->_entriesCount > $this->maxEntriesCount) {
            throw new Exception('Entries count exceeds limit of "' . $this->maxEntriesCount . '" at file "' . $this->getFullFileName() . '".');
        }

        return $this->_entriesCount;
    }

    /**
     * Returns the full file name.
     * @return string full file name.
     */
    public function getFullFileName()
    {
        return Yii::getAlias($this->fileBasePath) . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * Resolves given file path, making sure it exists and writeable.
     * @throws Exception on failure.
     * @param string $path file path.
     * @return bool success.
     */
    protected function resolvePath($path)
    {
        FileHelper::createDirectory($path, $this->filePermissions);
        if (!is_dir($path)) {
            throw new Exception("Unable to resolve path: '{$path}'!");
        } elseif (!is_writable($path)) {
            throw new Exception("Path: '{$path}' should be writeable!");
        }

        return true;
    }

    /**
     * Opens the related file for writing.
     * @throws Exception on failure.
     * @return bool success.
     */
    public function open()
    {
        if ($this->_fileHandler === null) {
            $this->resolvePath(dirname($this->getFullFileName()));
            $this->_fileHandler = fopen($this->getFullFileName(), 'w+');
            if ($this->_fileHandler === false) {
                throw new Exception('Unable to create/open file "' . $this->getFullFileName() . '".');
            }
            $this->afterOpen();
        }

        return true;
    }

    /**
     * Close the related file if it was opened.
     * @throws Exception if file exceed max allowed size.
     * @return bool success.
     */
    public function close()
    {
        if ($this->_fileHandler) {
            $this->beforeClose();
            fclose($this->_fileHandler);
            $this->_fileHandler = null;
            $this->_entriesCount = 0;
            $fileSize = filesize($this->getFullFileName());
            if ($fileSize > $this->maxFileSize) {
                throw new Exception('File "'.$this->getFullFileName().'" has exceed the size limit of "' . $this->maxFileSize . '": actual file size: "'.$fileSize.'".');
            }
        }

        return true;
    }

    /**
     * Writes the given content to the file.
     * @throws Exception on failure.
     * @param string $content content to be written.
     * @return int the number of bytes written.
     */
    public function write($content)
    {
        $this->open();

        $bytesWritten = fwrite($this->_fileHandler, $content);
        if ($bytesWritten === false) {
            throw new Exception('Unable to write file "' . $this->getFullFileName() . '".');
        }

        return $bytesWritten;
    }

    /**
     * This methods is invoked after the file is actually opened for writing.
     * You can override this method to perform some initialization,
     * in this case do not forget to call the parent implementation.
     */
    protected function afterOpen()
    {
        $this->write($this->header);

        if (!empty($this->rootTag)) {
            $tagOptions = $this->rootTag;
            $tagName = ArrayHelper::remove($tagOptions, 'tag');
            $this->write(Html::beginTag($tagName, $tagOptions));
        }
    }

    /**
     * This method is invoked before the file is actually closed.
     * You can override this method to perform some finalization.
     */
    protected function beforeClose()
    {
        if (!empty($this->rootTag)) {
            $tagOptions = $this->rootTag;
            $this->write(Html::endTag(ArrayHelper::remove($tagOptions, 'tag')));
        }

        $this->write($this->footer);
    }
}
