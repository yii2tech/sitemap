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
