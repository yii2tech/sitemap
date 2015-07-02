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
        $testLastModifiedDate = '2010-07-15';
        $testChangeFrequency = 'test_frequency';
        $testPriority = 0.2;
        $siteMapFile->writeUrl($testUrl, [
            'lastModified' => $testLastModifiedDate,
            'changeFrequency' => $testChangeFrequency,
            'priority' => $testPriority
        ]);

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
    public function testWriteUrlParams()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->writeUrl(['controller/action']);

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());
        $this->assertContains('http://test.com/index.php?r=controller%2Faction', $fileContent);
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
