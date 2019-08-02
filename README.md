<p align="center">
    <a href="https://github.com/yii2tech" target="_blank">
        <img src="https://avatars2.githubusercontent.com/u/12951949" height="100px">
    </a>
    <h1 align="center">Site Map Extension for Yii 2</h1>
    <br>
</p>

This extension provides support for site map and site map index files generating.

For license information check the [LICENSE](LICENSE.md)-file.

[![Latest Stable Version](https://poser.pugx.org/yii2tech/sitemap/v/stable.png)](https://packagist.org/packages/yii2tech/sitemap)
[![Total Downloads](https://poser.pugx.org/yii2tech/sitemap/downloads.png)](https://packagist.org/packages/yii2tech/sitemap)
[![Build Status](https://travis-ci.org/yii2tech/sitemap.svg?branch=master)](https://travis-ci.org/yii2tech/sitemap)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2tech/sitemap
```

or add

```json
"yii2tech/sitemap": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides support for site map and site map index files generation.
You can use `\yii2tech\sitemap\File` for site map file composition:

```php
<?php

use yii2tech\sitemap\File;

$siteMapFile = new File();

$siteMapFile->writeUrl(['site/index'], ['priority' => '0.9']);
$siteMapFile->writeUrl(['site/about'], ['priority' => '0.8', 'changeFrequency' => File::CHECK_FREQUENCY_WEEKLY]);
$siteMapFile->writeUrl(['site/signup'], ['priority' => '0.7', 'lastModified' => '2015-05-07']);
$siteMapFile->writeUrl(['site/contact']);

$siteMapFile->close();
```

In case you put sitemap generation into console command, you will need to manually configure URL manager
parameters for it. For example:

```php
<?php

return [
    'id' => 'my-console-application',
    'components' => [
        'urlManager' => [
            'hostInfo' => 'http://example.com',
            'baseUrl' => '/',
            'scriptUrl' => '/index.php',
        ],
        // ...
    ],
    // ...
];
```


## Creating site map index files <span id="creating-site-map-index-files"></span>

There is a limitation on the site map maximum size. Such file can not contain more then 50000 entries and its
actual size can not exceed 50MB. If you web application has more then 50000 pages and you need to generate
site map for it, you'll have to split it between several files and then generate a site map index file.
It is up to you how you split your URLs between different site map files, however you can use `\yii2tech\sitemap\File::getEntriesCount()`
or `\yii2tech\sitemap\File::getIsEntriesLimitReached()` method to check count of already written entries.

For example: assume we have an 'item' table, which holds several millions of records, each of which has a detail
view page at web application. In this case generating site map files for such pages may look like following:

```php
<?php

use yii2tech\sitemap\File;
use app\models\Item;

$query = Item::find()->select(['slug'])->asArray();

$siteMapFileCount = 0;
foreach ($query->each() as $row) {
    if (empty($siteMapFile)) {
        $siteMapFile = new File();
        $siteMapFileCount++;
        $siteMapFile->fileName = 'item_' . $siteMapFileCount . '.xml';
    }

    $siteMapFile->writeUrl(['item/view', 'slug' => $row['slug']]);
    if ($siteMapFile->getIsEntriesLimitReached()) {
        unset($siteMapFile);
    }
}
```

Once all site map files are generated, you can compose index file, using the following code:

```php
<?php

use yii2tech\sitemap\IndexFile;

$siteMapIndexFile = new IndexFile();
$siteMapIndexFile->writeUp();
```

> Note: by default site map files are stored under the path '@app/web/sitemap'. If you need a different file path
  you should adjust `fileBasePath` field accordingly.


## Rendering on-the-fly <span id="rendering-on-the-fly"></span>

Saving sitemap to the physical file may be not a best option to keep it up-to-date. Such file should be manually re-created
once some changes among site pages appear. You may setup a web controller, which will render 'sitemap.xml' file on demand
once it is been requested. This controller may apply caching and its busting logic.
First of all you'll have to set up a route for the controller action rendering the sitemap in your URL manager. For example:

```php
<?php

return [
    'components' => [
        'urlManager' => [
            'rules' => [
                'sitemap.xml' => 'site/sitemap',
                // ...
            ],
        ],
        // ...
    ],
    // ...
];
```

Then you'll need to create an action, which will render sitemap file content and emit it to the web client.
You can use PHP 'in memory' stream as a file name for the sitemap file during its composition.
The final implementation may look like following:

```php
<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii2tech\sitemap\File;

class SiteController extends Controller
{
    public function actionSitemap()
    {
        // get content from cache:
        $content = Yii::$app->cache->get('sitemap.xml');
        if ($content === false) {
            // create sitemap file in memory:
            $sitemap = new File();
            $sitemap->fileName = 'php://memory';
            
            // write your site URLs:
            $sitemap->writeUrl(['site/index'], ['priority' => '0.9']);
            // ...
            
            // get generated content:
            $content = $sitemap->getContent();

            // save generated content to cache
            Yii::$app->cache->set('sitemap.xml', $content);
        }

        // send sitemap content to the user agent:
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_RAW;
        $response->getHeaders()->add('Content-Type', 'application/xml;');
        $response->content = $content;
        
        return $response;
    }
}
```


## Customizing file envelope <span id="customizing-file-envelope"></span>

You can customize entries envelope for the particular file using following options:

 - `\yii2tech\sitemap\BaseFile::$header` - content, which should be written at the beginning of the file, once it has been opened;
 
 - `\yii2tech\sitemap\BaseFile::$footer` - content, which should be written at the end of the file before it is closed;
 
 - `\yii2tech\sitemap\BaseFile::$rootTag` - defines XML root tag name and attributes;
 
For example:

```php
<?php

use yii2tech\sitemap\File;

$siteMapFile = new File([
    'header' => '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="//example.com/main-sitemap.xsl"?>',
    'rootTag' => [
        'tag' => 'urlset',
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1',
        'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd',
    ],
]);

$siteMapFile->writeUrl(['site/index'], ['priority' => '0.9']);
// ...

$siteMapFile->close();
```


## Rendering non-standard tags <span id="rendering-non-standard-tags"></span>

While there is a [standard](http://www.sitemaps.org/), which defines sitemap content particular search engines may accept
extra tags and options. The most widely used are image and video descriptions.
Method `\yii2tech\sitemap\File::writeUrl()` supports rendering image and video information.

For adding images to the sitemap entry use 'images' option. For example:

```php
<?php

use yii2tech\sitemap\File;

$siteMapFile = new File([
    'rootTag' => [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1', // you will need to add XML namespace for non-standard tags
    ],
]);

$siteMapFile->writeUrl(['site/index'], [
    'images' => [
        [
            'url' => 'http://example.com/images/logo.jpg',
            'title' => 'Logo',
        ],
        [
            'url' => 'http://example.com/images/avatars/john-doe.jpg',
            'title' => 'Author',
        ],
        // ...
    ],
]);
// ...

$siteMapFile->close();
```

For adding videos to the sitemap entry use 'videos' option. For example:

```php
<?php

use yii2tech\sitemap\File;

$siteMapFile = new File([
    'rootTag' => [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:video' => 'http://www.google.com/schemas/sitemap-video/1.1', // you will need to add XML namespace for non-standard tags
    ],
]);

$siteMapFile->writeUrl(['site/index'], [
    'videos' => [
        [
            'title' => 'Demo video',
            'description' => 'Demo video of the main process',
            'thumbnailUrl' => 'http://example.com/images/demo-video.jpg',
            'player' => [
                'url' => 'http://example.com/videos/demo.flv',
                'allowEmbed' => true,
                'autoplay' => 'ap=1',
            ],
            'publicationDate' => '2019-08-02',
            'duration' => 240,
        ],
        [
            'title' => 'Our team',
            'description' => 'Greetings from our team',
            'thumbnailUrl' => 'http://example.com/images/our-team.jpg',
            'player' => [
                'url' => 'http://example.com/videos/our-team.flv',
                'allowEmbed' => true,
                'autoplay' => 'ap=1',
            ],
            'publicationDate' => '2019-08-02',
            'duration' => 120,
        ],
        // ...
    ],
]);
// ...

$siteMapFile->close();
```

You can also add any custom content to the URL tag using 3rd argument of the `\yii2tech\sitemap\File::writeUrl()` method.
For example:

```php
<?php

use yii2tech\sitemap\File;

$siteMapFile = new File([
    'rootTag' => [
        'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
        'xmlns:image' => 'http://www.google.com/schemas/sitemap-image/1.1', // you will need to add XML namespace for non-standard tags
    ],
]);

$siteMapFile->writeUrl(
    ['site/index'],
    [],
    '<image:image><image:loc>http://example.com/images/logo.jpg</image:loc></image:image>'
);
// ...

$siteMapFile->close();
```

**Heads up!** Remember that you'll have to add corresponding XML namespaces to the sitemap file, using `\yii2tech\sitemap\BaseFile::$rootTag`,
in order to non-standard tags being recognized by the search engines.
