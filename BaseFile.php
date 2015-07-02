<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace yii2tech\sitemap;

use Yii;
use yii\base\Exception;
use yii\base\Object;
use yii\di\Instance;
use yii\helpers\FileHelper;
use yii\web\UrlManager;

/**
 * BaseFile is a base class for the sitemap XML files.
 *
 * @see http://www.sitemaps.org/
 *
 * @property integer $entriesCount integer the count of entries written into the file, this property is read-only.
 * @property UrlManager|array|string $urlManager the URL manager object or the application component ID of the URL manager.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
abstract class BaseFile extends Object
{
    const MAX_ENTRIES_COUNT = 40000; // max XML entries count.
    const MAX_FILE_SIZE = 10485760; // max allowed file size in bytes = 10 MB

    /**
     * @var string name of the site map file.
     */
    public $fileName = 'sitemap.xml';
    /**
     * @var integer the chmod permission for directories and files,
     * created in the process. Defaults to 0777 (owner rwx, group rwx and others rwx).
     */
    public $filePermissions = 0777;
    /**
     * @var string directory, which should be used to store generated site map file.
     * By default '@app/web/sitemap' will be used.
     */
    public $fileBasePath = '@app/web/sitemap';
    /**
     * @var resource file resource handler.
     */
    private $_fileHandler;
    /**
     * @var integer the count of entries written into the file.
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
     * @return integer the count of entries written into the file.
     */
    public function getEntriesCount()
    {
        return $this->_entriesCount;
    }

    /**
     * @param UrlManager|array|string $urlManager
     */
    public function setUrlManager($urlManager)
    {
        $this->_urlManager = $urlManager;
    }

    /**
     * @return UrlManager
     */
    public function getUrlManager()
    {
        if (!is_object($this->_urlManager)) {
            $this->_urlManager = Instance::ensure($this->_urlManager, UrlManager::className());
        }
        return $this->_urlManager;
    }

    /**
     * Increments the internal entries count.
     * @throws Exception if limit exceeded.
     * @return integer new entries count value.
     */
    protected function incrementEntriesCount()
    {
        $this->_entriesCount++;
        if ($this->_entriesCount > self::MAX_ENTRIES_COUNT) {
            throw new Exception('Entries count exceeds limit of "' . self::MAX_ENTRIES_COUNT . '".');
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
     * @return boolean success.
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
     * @return boolean success.
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
     * @return boolean success.
     */
    public function close()
    {
        if ($this->_fileHandler) {
            $this->beforeClose();
            fclose($this->_fileHandler);
            $this->_fileHandler = null;
            $this->_entriesCount = 0;
            $fileSize = filesize($this->getFullFileName());
            if ($fileSize > self::MAX_FILE_SIZE) {
                throw new Exception('File "'.$this->getFullFileName().'" has exceed the size limit of "'.self::MAX_FILE_SIZE.'": actual file size: "'.$fileSize.'".');
            }
        }
        return true;
    }

    /**
     * Writes the given content to the file.
     * @throws Exception on failure.
     * @param string $content content to be written.
     * @return integer the number of bytes written.
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
        $this->write('<?xml version="1.0" encoding="UTF-8"?>');
    }

    /**
     * This method is invoked before the file is actually closed.
     * You can override this method to perform some finalization.
     */
    protected function beforeClose()
    {
        // blank
    }
}
