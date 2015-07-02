<?php

namespace yii2tech\tests\unit\sitemap;

use Yii;
use yii2tech\sitemap\File;

/**
 * Test case for the extension [[File]].
 * @see File
 */
class FileTest extends TestCase
{
    /**
     * Creates the site map file instance.
     * @return File
     */
    protected function createSiteMapFile()
    {
        $siteMapFile = new File();
        $siteMapFile->fileBasePath = $this->getTestFilePath();
        return $siteMapFile;
    }

    // Tests:

    public function testWriteBasicXml()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->write('');
        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains('<urlset', $fileContent);
        $this->assertContains('</urlset>', $fileContent);
    }

    public function testWriteUrl()
    {
        $siteMapFile = $this->createSiteMapFile();

        $testUrl = 'http://test.url';
        $testLastModifiedDate = date('Y-m-d');
        $testChangeFrequency = 'test_frequency';
        $testPriority = rand(1, 10)/10;
        $siteMapFile->writeUrl($testUrl, $testLastModifiedDate, $testChangeFrequency, $testPriority);

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains('<url', $fileContent);
        $this->assertContains('</url>', $fileContent);
        $this->assertContains($testUrl, $fileContent, 'XML does not contains the URL!');
        $this->assertContains($testLastModifiedDate, $fileContent, 'XML does not contains the last modified date!');
        $this->assertContains($testChangeFrequency, $fileContent, 'XML does not contains the change frequency!');
        $this->assertContains((string)$testPriority, $fileContent, 'XML does not contains the priority!');
    }

    /**
     * @depends testWriteUrl
     */
    public function testEntriesCountIncrement()
    {
        $siteMapFile = $this->createSiteMapFile();

        $originalEntriesCount = $siteMapFile->getEntriesCount();
        $this->assertEquals(0, $originalEntriesCount, 'Original entries count is wrong!');

        $testUrl = 'http://test.url';
        $siteMapFile->writeUrl($testUrl);

        $postWriteEntriesCount = $siteMapFile->getEntriesCount();

        $this->assertEquals($originalEntriesCount + 1, $postWriteEntriesCount, 'No entries count increment detected!');
        $siteMapFile->close();
    }
}
