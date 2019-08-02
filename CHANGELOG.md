Yii 2 Site Map extension Change Log
===================================

1.1.0, August 2, 2019
---------------------

- Enh: Removed `yii\base\Object::className()` in favor of native PHP syntax `::class`, which does not trigger autoloading (klimov-paul)
- Enh: Added support for 'images' and 'videos' options to `File::writeUrl()` (klimov-paul)
- Enh: Added ability to pass extra XML content to `File::writeUrl()` (klimov-paul)
- Enh: Extracted special `LimitReachedException` exception class (klimov-paul)
- Enh: Added ability to use PHP stream as `BaseFile::$fileName` (klimov-paul)
- Enh #5: Added `header`, `footer` and `rootTag` fields to `BaseFile` allowing customizing of the file entries envelope (GeniJaho, klimov-paul)
- Enh #6: Added `BaseFile::$lineBreak` allowing setup of the lines separator (easelify)


1.0.2, January 24, 2019
-----------------------

- Enh #3: Sitemap file name added to the message of the "max entries exceed" exception (machour, klimov-paul)
- Enh #4: Sitemap files now skip rendering of the optional tags if their value is `null` (OndrejVasicek, klimov-paul)


1.0.1, November 3, 2017
-----------------------

- Bug: Usage of deprecated `yii\base\Object` changed to `yii\base\BaseObject` allowing compatibility with PHP 7.2 (klimov-paul)


1.0.0, December 26, 2015
------------------------

- Initial release.
