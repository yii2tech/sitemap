<?php

namespace yii2tech\tests\unit\sitemap;

use Yii;
use yii2tech\sitemap\IndexFile;

/**
 * Test case for the extension [[IndexFile]].
 * @see IndexFile
 */
class IndexFileTest extends TestCase
{
    /**
     * Creates the site map index file instance.
     * @return IndexFile
     */
    protected function createSiteMapIndexFile()
    {
        $siteMapFile = new IndexFile();
        $siteMapFile->fileBasePath = $this->getTestFilePath();
        return $siteMapFile;
    }

    // Tests:

    public function testSetGet()
    {
        $siteMapIndexFile = new IndexFile();

        $testFileBaseUrl = 'http://test.file/base/url';
        $siteMapIndexFile->setFileBaseUrl($testFileBaseUrl);
        $this->assertEquals($testFileBaseUrl, $siteMapIndexFile->getFileBaseUrl(), 'Unable to set file base URL correctly!');
    }

    /**
     * @depends testSetGet
     */
    public function testGetDefaultFileBaseUrl()
    {
        $siteMapIndexFile = new IndexFile();

        $defaultFileBaseUrl =$siteMapIndexFile->getFileBaseUrl();
        $this->assertNotEmpty($defaultFileBaseUrl, 'Unable to get default file base URL!');
    }

    public function testWriteBasicXml()
    {
        $siteMapIndexFile = $this->createSiteMapIndexFile();

        $siteMapIndexFile->write('');
        $siteMapIndexFile->close();

        $fileContent = file_get_contents($siteMapIndexFile->getFullFileName());

        $this->assertContains('<sitemapindex', $fileContent);
        $this->assertContains('</sitemapindex>', $fileContent);
    }

    public function testWriteSiteMap()
    {
        $siteMapIndexFile = $this->createSiteMapIndexFile();

        $testSiteMapFileUrl = 'http://test.url';
        $testLastModifiedDate = date('Y-m-d');

        $siteMapIndexFile->writeSiteMap($testSiteMapFileUrl, $testLastModifiedDate);
        $siteMapIndexFile->close();

        $fileContent = file_get_contents($siteMapIndexFile->getFullFileName());

        $this->assertContains('<sitemap', $fileContent);
        $this->assertContains('</sitemap>', $fileContent);
        $this->assertContains($testSiteMapFileUrl, $fileContent, 'XML does not contains the site map file URL!');
        $this->assertContains($testLastModifiedDate, $fileContent, 'XML does not contains the last modified date!');
    }

    /**
     * @depends testWriteSiteMap
     */
    public function testEntriesCountIncrement()
    {
        $siteMapIndexFile = $this->createSiteMapIndexFile();

        $originalEntriesCount = $siteMapIndexFile->getEntriesCount();
        $this->assertEquals(0, $originalEntriesCount, 'Original entries count is wrong!');

        $testSiteMapFileUrl = 'http://test.url';

        $siteMapIndexFile->writeSiteMap($testSiteMapFileUrl);

        $postWriteEntriesCount = $siteMapIndexFile->getEntriesCount();

        $this->assertEquals($originalEntriesCount+1, $postWriteEntriesCount, 'No entries count increment detected!');
        $siteMapIndexFile->close();
    }

    /**
     * @depends testSetGet
     * @depends testWriteSiteMap
     */
    public function testWriteUpFromPath()
    {
        $siteMapIndexFile = $this->createSiteMapIndexFile();

        $testFileBaseUrl = 'http://test.file/base/path';
        $siteMapIndexFile->setFileBaseUrl($testFileBaseUrl);
        $testFilePath = $this->getTestFilePath();

        $testFileNames = [];
        $testFileNamePrefix = 'test_file_';
        $testFilesCount = 4;
        for ($i=1; $i<=$testFilesCount; $i++) {
            $fileExtension = ($i%2==0) ? 'xml' : 'gzip';
            $testFileName = $testFileNamePrefix.$i.'.'.$fileExtension;
            $testFileNames[] = $testFileName;
            $testFullFileName = $testFilePath.DIRECTORY_SEPARATOR.$testFileName;
            file_put_contents($testFullFileName, 'test content '.$i);
        }

        $writtenFilesCount = $siteMapIndexFile->writeUpFromPath($testFilePath);
        $this->assertEquals($testFilesCount, $writtenFilesCount, 'Unable to write up from path!');

        $fileContent = file_get_contents($siteMapIndexFile->getFullFileName());
        foreach ($testFileNames as $testFileName) {
            $this->assertContains($testFileName, $fileContent, 'File name not present in the XML!');
            $fileUrl = $testFileBaseUrl.'/'.$testFileName;
            $this->assertContains($fileUrl, $fileContent, 'File URL not present in the XML!');
        }
    }

    /**
     * @depends testWriteUpFromPath
     */
    public function testWriteUp()
    {
        $siteMapIndexFile = $this->createSiteMapIndexFile();

        $testFilePath = $siteMapIndexFile->fileBasePath;

        $testFileNames = [];
        $testFileNamePrefix = 'test_file_';
        $testFilesCount = 4;
        for ($i=1; $i<=$testFilesCount; $i++) {
            $fileExtension = ($i % 2 === 0) ? 'xml' : 'gzip';
            $testFileName = $testFileNamePrefix . $i . '.' . $fileExtension;
            $testFileNames[] = $testFileName;
            $testFullFileName = $testFilePath . DIRECTORY_SEPARATOR . $testFileName;
            file_put_contents($testFullFileName, 'test content '.$i);
        }

        $writtenFilesCount = $siteMapIndexFile->writeUpFromPath($testFilePath);
        $this->assertEquals($testFilesCount, $writtenFilesCount, 'Unable to write up!');
    }
}