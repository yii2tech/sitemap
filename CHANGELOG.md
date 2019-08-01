Yii 2 Site Map extension Change Log
===================================

1.1.0 Under Development
-----------------------

- Enh: Removed `yii\base\Object::className()` in favor of native PHP syntax `::class`, which does not trigger autoloading (klimov-paul)


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
