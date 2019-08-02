<?php

namespace yii2tech\tests\unit\sitemap;

use yii2tech\sitemap\File;
use yii2tech\sitemap\LimitReachedException;

/**
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

        $this->assertContains('<?xml', $fileContent);
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
    public function testWriteUrlDefaultOptions()
    {
        $siteMapFile = $this->createSiteMapFile();
        $siteMapFile->defaultOptions = [
            'lastModified' => '2010-01-01',
            'changeFrequency' => 'test_frequency',
            'priority' => '0.1'
        ];

        $siteMapFile->writeUrl('http://test.url');
        $siteMapFile->close();
        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains($siteMapFile->defaultOptions['lastModified'], $fileContent);
        $this->assertContains($siteMapFile->defaultOptions['changeFrequency'], $fileContent);
        $this->assertContains($siteMapFile->defaultOptions['priority'], $fileContent);
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

    /**
     * @depends testEntriesCountIncrement
     */
    public function testEntiesCountExceedException()
    {
        $siteMapFile = $this->createSiteMapFile();

        $this->expectException(LimitReachedException::class);
        for ($i = 1; $i < $siteMapFile->maxEntriesCount + 2; $i++) {
            $siteMapFile->writeUrl('http://test.url');
        }
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteUrlWithDefaultOptions()
    {
        $siteMapFile = $this->createSiteMapFile();

        $testUrl = 'http://test.url';
        $siteMapFile->writeUrl($testUrl);

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains("<url><loc>{$testUrl}</loc></url>", $fileContent);
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteUrlWithInvalidOption()
    {
        $siteMapFile = $this->createSiteMapFile();

        $this->expectException(\yii\base\InvalidArgumentException::class);

        $siteMapFile->writeUrl('http://test.url', [
            'invalidOption' => 'some-value',
        ]);
    }

    /**
     * @depends testWriteBasicXml
     */
    public function testCustomizeEnvelope()
    {
        $siteMapFile = $this->createSiteMapFile();
        $siteMapFile->header = '<!-- header -->';
        $siteMapFile->footer = '<!-- footer -->';
        $siteMapFile->rootTag = [
            'tag' => 'myurlset',
            'xmlns' => 'http://example.com',
        ];

        $siteMapFile->write('');
        $siteMapFile->close();

        $fileContent = str_replace($siteMapFile->lineBreak, '', file_get_contents($siteMapFile->getFullFileName()));

        $expectedContent = '<!-- header --><myurlset xmlns="http://example.com"></myurlset><!-- footer -->';

        $this->assertSame($expectedContent, $fileContent);
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteImages()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->writeUrl('http://example.com/some', [
            'images' => [
                [
                    'url' => 'http://example.com/images/1.jpg',
                    'title' => 'test title',
                    'caption' => 'test caption',
                    'geoLocation' => 'test location',
                    'license' => 'test license',
                ],
                [
                    'url' => 'http://example.com/images/2.jpg',
                ],
            ],
        ]);

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains('<image:image>', $fileContent);
        $this->assertContains('</image:image>', $fileContent);

        $this->assertContains('<image:loc>http://example.com/images/1.jpg</image:loc>', $fileContent);
        $this->assertContains('<image:loc>http://example.com/images/2.jpg</image:loc>', $fileContent);

        $this->assertContains('<image:title>test title</image:title>', $fileContent);
        $this->assertContains('<image:caption>test caption</image:caption>', $fileContent);
        $this->assertContains('<image:geo_location>test location</image:geo_location>', $fileContent);
        $this->assertContains('<image:license>test license</image:license>', $fileContent);
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteVideos()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->writeUrl('http://example.com/some', [
            'videos' => [
                [
                    'title' => 'test title',
                    'description' => 'test description',
                    'thumbnailUrl' => 'http://example.com/images/thumbnail.jpg',
                    'player' => [
                        'url' => 'http://example.com/videos/1.flv',
                        'allowEmbed' => true,
                        'autoplay' => 'ap=1',
                    ],
                    'publicationDate' => '2019-08-02',
                    'duration' => 120,
                ],
                [
                    'player' => [
                        'url' => 'http://example.com/videos/2.flv',
                        'allowEmbed' => true,
                        'autoplay' => 'ap=1',
                    ],
                ],
            ],
        ]);

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains('<video:video>', $fileContent);
        $this->assertContains('</video:video>', $fileContent);

        $this->assertContains('<video:player_loc allow_embed="yes" autoplay="ap=1">http://example.com/videos/1.flv</video:player_loc>', $fileContent);
        $this->assertContains('<video:player_loc allow_embed="yes" autoplay="ap=1">http://example.com/videos/2.flv</video:player_loc>', $fileContent);
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteExtraContent()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->writeUrl(
            'http://example.com/some',
            [],
            '<!-- extra-content -->'
        );

        $siteMapFile->close();

        $fileContent = file_get_contents($siteMapFile->getFullFileName());

        $this->assertContains('<!-- extra-content --></url>', $fileContent);
    }

    /**
     * @depends testWriteUrl
     */
    public function testWriteInMemory()
    {
        $siteMapFile = $this->createSiteMapFile();

        $siteMapFile->fileName = 'php://memory';

        $siteMapFile->writeUrl('http://example.com/foo');

        $fileContent = $siteMapFile->getContent();

        $this->assertContains('<?xml', $fileContent);
        $this->assertContains('http://example.com/foo', $fileContent);
    }
}
