<?php

namespace yii2tech\tests\unit\sitemap;

use Yii;
use yii\helpers\FileHelper;
use yii2tech\sitemap\BaseFile;

/**
 * Test case for the extension [[BaseFile]].
 * @see BaseFile
 */
class BaseFileTest extends TestCase
{
    /**
     * Creates the site map file instance.
     * @return BaseFile
     */
    protected function createSiteMapFile()
    {
        $siteMapFileMock = $this->getMock(BaseFile::className(), ['blank']);
        return $siteMapFileMock;
    }

    // Tests:

    public function testGetFullFileName()
    {
        $siteMapFile = $this->createSiteMapFile();

        $testFileBasePath = '/test/file/path';
        $siteMapFile->fileBasePath = $testFileBasePath;
        $testFileName = 'test_file_name.xml';
        $siteMapFile->fileName = $testFileName;

        $expectedFullFileName = $testFileBasePath . DIRECTORY_SEPARATOR . $testFileName;
        $fullFileName = $siteMapFile->getFullFileName();
        $this->assertEquals($expectedFullFileName, $fullFileName, 'Unable to get full file name correctly!');
    }

    /**
     * @depends testGetFullFileName
     */
    public function testWrite()
    {
        $siteMapFile = $this->createSiteMapFile();
        $siteMapFile->fileBasePath = $this->getTestFilePath();

        $testFileContent = 'Test File Content';
        $siteMapFile->write($testFileContent);
        $siteMapFile->close();

        $fullFileName = $siteMapFile->getFullFileName();
        $this->assertTrue(file_exists($fullFileName), 'Unable to create a file!');

        $fileActualContent = file_get_contents($fullFileName);
        $this->assertContains($testFileContent, $fileActualContent, 'File has wrong content!');
    }
}
