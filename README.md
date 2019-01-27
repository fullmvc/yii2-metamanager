Meta tag manager
==================
This is an extension for yii2 to help sites adding meta tag for better SEO score.

[![Total Downloads](https://poser.pugx.org/fullmvc/yii2-metamanager/downloads)](https://packagist.org/packages/fullmvc/yii2-metamanager)

Installation
------------

The simplest way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist fullmvc/yii2-metamanager "dev-master"
```

or add

```
"fullmvc/yii2-metamanager": "dev-master"
```

to the require section of your `composer.json` file.

Config
-----

After you install this extension, you need to add this as a component in your config file like this:

```php
'components' => [
    'metaManager' => 'fullmvc\metamanager\MetaManager',
],
```

Here you can specify the default values for each tag like:

```php
'components => [
    'metaManager' => [
        'class' => 'fullmvc\metamanager\MetaManager',
        'defaultMetaDatas' => [
            'DC.description' => ['content' => 'My website\'s title'],
            'DC.title' => ['content' => 'This is my very own website'],
            'twitter:description' => ['content' => 'This is my very own website'],
            'twitter:title' => ['content' => 'My website\'s title'],
            'og:description' => ['content' => 'This is my very own website'],
            'og:title' => ['content' => 'My website\'s title'],
        ],
    ],
]
```

Usage
-----

Call directly the register methods:

```php
Yii::$app->metaManager->registerTitle('My website\'s title');
Yii::$app->metaManager->registerDescription('This is my very own website');
```

or register a model and the class will do all the work for you:

```php
Yii::$app->metaManager->registerModel($myModel);
```

Pay attention to that the model need to have certain attribtues or getter
methods to be able to add the meta tags that you want:
- Base title gethered via: getMetaTitle() method or metaTitle attribute,
    getTitle() method or title attribute of the model
- Base description gethered via: getMetaDescription() method or metaDescription
    attribute, getDescription() method or description attribute of the model
- Base keywords gethered via: getMetaKeywords() method or metaKeywords attribute,
    getKeywords() method or keywords attribute of the model
- Base image gethered via: getMetaImageUrl() method or metaImageUrl attribute,
    getImageUrl() method or imageUrl attribute of the model

These basic methods could be overwriten by specifing it in your config file like:

```php
'components' => [
    'metaManager' => [
        'class' => 'fullmvc\metamanager\MetaManager',
        'metaAttributes'=> [
            'common\models\Blog' => [ // for a specific model
                'title' => 'myTitleAttribute',
                'description' => 'myDescriptionAttribute',
                'image' => 'myImageAttribute'
            ],

            ...

            [ // or by globally
                'title' => 'myTitleAttribute',
                'description' => 'myDescriptionAttribute',
                'image' => 'myImageAttribute'
            ]
        ]
    ]
],
```