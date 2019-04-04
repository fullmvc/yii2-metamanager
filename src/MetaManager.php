<?php

namespace fullmvc\metamanager;

use Yii;
use yii\base\Component;
use yii\base\UnknownPropertyException;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\imagine\Image;

/**
 * This is an extension for yii2 to help sites adding meta tag for better SEO score.
 *
 * Class MetaManager
 * @package fullmvc\metamanager
 */
class MetaManager extends Component
{
    /**
     * The event that is triggered when the record is initialized via [[init()]].
     *
     * @event Event
     */
    const EVENT_INIT = 'init';

    /**
     * When the metaAttributes registered to the MetaManager, it will use these attributes
     * for the registered model, instead of the default getTitle, getDescription and
     * getImage methods.
     *
     * You may specify the model namespace for the key if you want to restrict for only one
     * specific model, like this:
     *
     * ```php
     * $MetaManager->setMetaAttributes([
     *      'common\models\Blog' => [
     *          'title' => 'myTitleAttribute',
     *          'description' => 'myDescriptionAttribute',
     *          'image' => 'myImageAttribute',
     *      ]
     * ]);
     * ```
     *
     * When the keys are not string or the namespace not accessible, it will use for all the
     * registered models.
     * For example,
     *
     * ```php
     * $MetaManager->setMetaAttributes([
     *      'common\models\Blog' => [
     *          'title' => 'myTitleAttribute',
     *          'description' => 'myDescriptionAttribute',
     *      ]
     * ]);
     * ```
     *
     * When you need to specify a more complex scenario, you could use a callable for the key
     * like this.
     *
     * ```php
     * $MetaManager->setMetaAttributes([
     *      'common\models\User' => [
     *          'title' => function($user) {
     *              return $user->first_name . ' ' . $user->last_name;
     *          },
     *      ]
     * ]);
     * ```
     *
     * @var array
     */
    public $metaAttributes;

    /**
     * Optional switch to disable the registering of the meta keywords. Only works when using
     * the default attributes.
     *
     * @var bool
     */
    public $registerMetaKeywords = true;

    /**
     * Optional switch to register OpenGraph meta tags or not.
     *
     * @var bool
     */
    public $registerOgs = true;

    /**
     * Optional switch to register Dublin Core meta tags or not.
     *
     * @var bool
     */
    public $registerDcs = true;

    /**
     * Optional switch to register Twitter meta tags or not.
     *
     * @var bool
     */
    public $registerTwitters = true;

    /**
     * A key value pair for the default meta attributes and values.
     * Usage:
     *
     * ```php
     * 'components' => [
     *      'metaManager' => [
     *          'class' => 'fullmvc\metamanager\MetaManager',
     *          'defaultMetaDatas' => [
     *              'DC.title' => ['content' => 'My site\'s title'],
     *              'DC.description' => ['content' => 'This is my site'],
     *              'og:title' => ['content' => 'My site\'s title'],
     *              'og:description' => ['content' => 'This is my site'],
     *          ]
     *      ]
     * ]
     * ```
     *
     * or register all title and description at once:
     *
     * ```php
     * 'components' => [
     *      'metaManager' => [
     *          'class' => 'fullmvc\metamanager\MetaManager',
     *          'defaultMetaDatas' => [
     *              'title' => ['content' => 'My site\'s title'],
     *              'description' => ['content' => 'This is my site'],
     *          ]
     *      ]
     * ]
     * ```
     *
     * @var array
     */
    public $defaultMetaDatas;

    public $disabledOnAjax = true;
    public $disabledOnPjax = true;

    /**
     * @var yii\web\View
     */
    protected $_view;

    private $_title;

    /************************************ Base functions ************************************/

    /**
     * Initializes the object.
     * This method is called at the end of the constructor.
     * The default implementation will trigger an [[EVENT_INIT]] event.
     */
    public function init()
    {
        parent::init();

        if (!empty($this->defaultMetaDatas)) {
            foreach($this->defaultMetaDatas as $key => $metaTag) {
                if (method_exists($this, 'register' . ucfirst(strtolower($key)))) {
                    call_user_func([$this, 'register' . ucfirst(strtolower($key))], $metaTag);
                    continue;
                }

                if(!is_int($key) && !isset($metaTag['name'])) {
                    $metaTag['name'] = $key;
                }

                $this->registerMetaTag($metaTag);
            }
        }

        $this->trigger(self::EVENT_INIT);
    }

    /**
     * Returns the view object that can be used to render views or view files.
     * The [[render()]] and [[renderFile()]] methods will use
     * this view object to implement the actual view rendering.
     * If not set, it will default to the "view" application component.
     *
     * @return \yii\web\View the view object that can be used to render views or view files.
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

        return $this->_view;
    }

    /**
     * Sets the view object to be used by this widget.
     *
     * @param \yii\web\View $view the view object that can be used to render views or view files.
     * @return MetaManager
     */
    public function setView($view)
    {
        $this->_view = $view;

        return $this;
    }

    /************************************ Setting up the tags ************************************/

    /**
     * Register a single meta tag.
     *
     * @param array $options
     * @param MetaManager $key
     */
    public function registerMetaTag($options, $key = null)
    {
        if (
            (!$this->disabledOnAjax || !Yii::$app->request->getIsAjax()) &&
            (!$this->disabledOnPjax || !Yii::$app->request->getIsPjax())
        ) {
            $this->getView()->registerMetaTag($options, $key);
            asort($this->getView()->metaTags);
        }

        return $this;
    }

    /**
     * Unregisters a specifis meta tag.
     *
     * @param string $key
     * @return MetaManager
     */
    public function clearMetaTag($key)
    {
        $view = $this->getView();
        if (isset($view->metaTags[$key])) {
            unset($view->metaTags[$key]);
        }

        return $this;
    }

    /**
     * Registers multiple meta tags.
     *
     * @param array $tags
     * @return MetaManager
     */
    public function registerMetaTags($tags)
    {
        foreach ($tags as $key => $options) {
            if (is_numeric($key)) {
                $this->registerMetaTag($options);
            } else {
                $this->registerMetaTag($options, $key);
            }
        }

        return $this;
    }

    /**
     * Registers multiple link tags.
     *
     * @param array $links
     * @return MetaManager
     */
    public function registerLinkTags($links)
    {
        foreach ($links as $key => $options) {
            if (is_numeric($key)) {
                $this->registerLinkTag($options);
            } else {
                $this->registerLinkTag($options, $key);
            }
        }

        return $this;
    }

    /**
     * Registers a link tag.
     *
     * @param array $options
     * @param MetaManager $key
     * @return MetaManager
     */
    public function registerLinkTag($options, $key = null)
    {
        if (
            (!$this->disabledOnAjax || !Yii::$app->request->getIsAjax()) &&
            (!$this->disabledOnPjax || !Yii::$app->request->getIsPjax())
        ) {
            $this->getView()->registerLinkTag($options, $key);
            asort($this->getView()->linkTags);
        }

        return $this;
    }

    /**
     * Unregisters a link tag.
     *
     * @param string $key
     * @return MetaManager
     */
    public function clearLinkTag($key)
    {
        $view = $this->getView();
        if (isset($view->linkTags[$key])) {
            unset($view->linkTags[$key]);
        }

        return $this;
    }

    /************************************ Social registration ************************************/

    /**
     * This registers multiple OpenGraph tags at the same time.
     * Usage:
     *
     * ```php
     * $MetaManager->registerOgs([
     *      'title' => 'My OG title',
     *      'description' => 'My OG description',
     * ]);
     * ```
     * And this generates:
     * <meta property="og:title" content="My OG title">
     * <meta property="og:description" content="My OG description">
     *
     * @param array $ogs
     * @return MetaManager
     */
    public function registerOgs($ogs)
    {
        if ($this->registerOgs) {
            foreach ($ogs as $property => $content) {
                $this->registerOg($property, $content);
            }
        }

        return $this;
    }

    /**
     * This registers an OpenGraph tag.
     * Usage:
     *
     * ```php
     * $MetaManager->registerOg('title', 'My OG title');
     * ```
     * And this generates:
     * <meta property="og:title" content="My OG title">
     *
     * @param string $property
     * @param string $content
     * @return MetaManager
     */
    public function registerOg($property, $content)
    {
        if ($this->registerOgs) {
            $key = 'og:' . $property;
            is_null($content)
                ? $this->clearMetaTag($key)
                : ($key == 'og:locale:alternate'
                ? $this->registerMetaTag(['property' => $key, 'content' => $content])
                : $this->registerMetaTag(['property' => $key, 'content' => $content], $key)
            );
        }

        return $this;
    }

    /**
     * This registers multiple Twitter tags at the same time.
     * Usage:
     *
     * ```php
     * $MetaManager->registerTwitters([
     *      'title' => 'My Twitter title',
     *      'description' => 'My Twitter description',
     * ]);
     * ```
     * And this generates:
     * <meta property="twitter:title" content="My Twitter title">
     * <meta property="twitter:description" content="My Twitter description">
     *
     * @param array $twitters
     * @return MetaManager
     */
    public function registerTwitters($twitters)
    {
        if ($this->registerTwitters) {
            foreach ($twitters as $name => $content) {
                $this->registerTwitter($name, $content);
            }
        }

        return $this;
    }

    /**
     * This registers a Twitter tag.
     * Usage:
     *
     * ```php
     * $MetaManager->registerTwitter('title' => 'My Twitter title');
     * ```
     * And this generates:
     * <meta property="twitter:title" content="My Twitter title">
     *
     * @param string $name
     * @param string $content
     * @return MetaManager
     */
    public function registerTwitter($name, $content)
    {
        if ($this->registerTwitters) {
            $key = 'twitter:' . $name;
            is_null($content)
                ? $this->clearMetaTag($key)
                : $this->registerMetaTag(['name' => $key, 'content' => $content], $key);
        }

        return $this;
    }

    /**
     * This registers multiple Dublin Core tags at the same time.
     * Usage:
     *
     * ```php
     * $MetaManager->registerDcs([
     *      'title' => 'My Dublin Core title',
     *      'description' => 'My Dublin Core description',
     * ]);
     * ```
     * And this generates:
     * <meta property="DC.title" content="My Dublin Core title">
     * <meta property="DC.description" content="My Dublin Core description">
     *
     * @param array $dcs
     * @return MetaManager
     */
    public function registerDcs($dcs)
    {
        if ($this->registerDcs) {
            foreach ($dcs as $name => $content) {
                $this->registerDc($name, $content);
            }
        }

        return $this;
    }

    /**
     * This registers a Dublin Core tag.
     * Usage:
     *
     * ```php
     * $MetaManager->registerDc('title', 'My Dublin Core title');
     * ```
     * And this generates:
     * <meta property="DC.title" content="My Dublin Core title">
     *
     * @param string $name
     * @param string $content
     * @return MetaManager
     */
    public function registerDc($name, $content)
    {
        if ($this->registerDcs) {
            $key = 'DC.' . $name;
            is_null($content)
                ? $this->clearMetaTag($key)
                : $this->registerMetaTag(['name' => $key, 'content' => $content], $key);
        }

        return $this;
    }

    /**
     * This registers one or more breadcrumb to the view.
     * Usage:
     *
     * ```php
     * $MetaManager->addBreadcrumbs([
     *      ['label' => Yii::t('app', 'Blogs'), 'url' => ['blog/index']],
     *      'Categories'
     * ]);
     * ```
     *
     * @param string|array $breadcrumb
     * @return MetaManager
     */
    public function addBreadcrumbs($breadcrumb)
    {
        $this->getView()->params['breadcrumbs'] [] = $breadcrumb;

        return $this;
    }

    /************************************  Helpers ************************************/

    /**
     * This function sets the all of the different title tags, such as DC, OG, etc.
     *
     * @param null $title
     * @param bool $addBreadcrumb
     * @return MetaManager
     */
    public function registerTitle($title = null, $addBreadcrumb = true)
    {
        $this->getView()->title = $title;
        $this->registerOg('title', $title);
        $this->registerTwitter('title', $title);
        $this->registerDc('title', $title);

        if ($addBreadcrumb) {
            $this->addBreadcrumbs($title);
        }

        $this->_title = $title;

        return $this;
    }

    /**
     * This function sets all of the different description tags, such as DC, OG, etc.
     *
     * @param string $description
     * @param int $length
     * @return MetaManager
     */
    public function registerDescription($description, $length = 150)
    {
        if (!empty($description)) {
            $description = trim(preg_replace('/\s\s+/', ' ', $description));

            if (strlen($description) > $length) {
                $description = ($pos = strrpos(substr($description, 0, $length), ' ')) !== false
                    ? substr($description, 0, $pos) . '...'
                    : StringHelper::truncateWords($description, $length);
            }

            $this->registerMetaTag(['name' => 'description', 'content' => $description], 'description');
            $this->registerOg('description', $description);
            $this->registerTwitter('description', $description);
            $this->registerDc('description', $description);
        }

        return $this;
    }

    /**
     * This function sets the meta keywords. Keep in mind, that google does not use the meta keywords
     * for ranking your page in the search results, but other search engines probably use it.
     *
     * @param string|array $description
     * @return MetaManager
     */
    public function registerKeywords($keywords)
    {
        if ($this->registerMetaKeywords && !empty($keywords)) {
            $this->registerMetaTag(['name' => 'keywords', 'content' => is_array($keywords) ? implode(',', $keywords) : $keywords], 'keywords');
        }

        return $this;
    }

    /**
     * This function registers the OG and Twitter image tags.
     *
     * @param string $url
     * @param string $alt
     * @return MetaManager
     */
    public function registerImage($url, $alt)
    {
        $filename = Yii::getAlias($url);

        if (file_exists($filename) && is_file($filename)) {
            $image = Url::to([$url], true);
            $imageObject = Image::getImagine()->open($filename);
            $imageWidth = $imageObject->getSize()->getWidth();
            $imageHeight = $imageObject->getSize()->getHeight();
        } else {
            $image = $url;
            $imageWidth = null;
            $imageHeight = null;
        }

        $this->registerOgs([
            'image' => $image,
            'image:secure_url' => $image,
            'image:width' => $imageWidth,
            'image:height' => $imageHeight,
            'image:alt' => $alt,
        ]);

        $this->registerTwitters([
            'image' => $image,
            'image:alt' => $alt,
        ]);

        return $this;
    }

    /**
     * This function sets the image for the OpenGraph and Twitter tags.
     *
     * @param string $url
     * @param string $alt
     * @param null|int $width
     * @param null|int $height
     * @return MetaManager
     */
    public function registerImageUrl($url, $alt, $width = null, $height = null)
    {
        $this->registerOgs([
            'image' => $url,
            'image:secure_url' => $url,
            'image:width' => $width,
            'image:height' => $height,
            'image:alt' => $alt,
        ]);

        $this->registerTwitters([
            'image' => $url,
            'image:alt' => $alt,
        ]);

        return $this;
    }

    /************************************ Model/attribute registration ************************************/

    /**
     * Setting the meta attributes to use with the model(s).
     *
     * @param array $metaAttributes
     * @param bool $dropOldAttributes
     * @return MetaManager
     */
    public function registerMetaAttributes($metaAttributes, $dropOldAttributes = false)
    {
        if ($dropOldAttributes || is_null($this->metaAttributes)) {
            $this->metaAttributes = [];
        }

        $this->metaAttributes = array_merge($this->metaAttributes, $metaAttributes);

        return $this;
    }

    /**
     * Register model for the source of the meta attributes.
     *
     * @param yii\base\Model $model
     * @return MetaManager
     * @throws UnknownPropertyException
     */
    public function registerModel($model)
    {
        if (!empty($this->metaAttributes)) {
            $globalAttributes = null;
            $modelSpecificAttributes = null;

            foreach ($this->metaAttributes as $key => $attributes) {
                if (is_int($key)) {
                    // these attributes used for all models
                    $globalAttributes = $attributes;
                    continue;
                }

                if (is_a($model, $key)) {
                    // these attributes specified only for this model
                    $modelSpecificAttributes = $attributes;
                    break;
                }
            }

            if (!empty($modelSpecificAttributes)) {
                // model specific attributes are found
                $this->registerMetasWithUniqueAttributes($model, $modelSpecificAttributes);
            }

            if (!empty($globalAttributes)) {
                // not found any model specific attributes, but found global ones
                $this->registerMetasWithUniqueAttributes($model, $globalAttributes);
                return $this;
            }
        }

        $this->registerMetasWithDefaultAttributes($model);

        return $this;
    }

    /**
     * This registers straight a model with default meta attribute getters.
     *
     * @param yii\base\Model $model
     * @return MetaManager
     */
    public function registerMetasWithDefaultAttributes($model)
    {
        if ($model->hasMethod('getMetaTitle')) {
            $this->registerTitle($model->getMetaTitle());
        } elseif ($model->hasMethod('getTitle')) {
            $this->registerTitle($model->getTitle());
        } else if ($model->hasProperty('title')) {
            $this->registerTitle($model->title);
        }

        if ($model->hasMethod('getMetaDescription')) {
            $this->registerDescription($model->getMetaDescription());
        } elseif ($model->hasMethod('getDescription')) {
            $this->registerDescription($model->getDescription());
        } elseif ($model->hasProperty('description')) {
            $this->registerDescription($model->description);
        }

        if ($model->hasMethod('getMetaKeywords')) {
            $this->registerKeywords($model->getMetaKeywords());
        } elseif ($model->hasMethod('getKeywords')) {
            $this->registerKeywords($model->getKeywords());
        } elseif ($model->hasProperty('keywords')) {
            $this->registerKeywords($model->keywords);
        }

        $metaImageUrl = null;
        $metaImageAlt = null;
        $metaImageWidth = null;
        $metaImageHeight = null;

        if ($model->hasMethod('getMetaImageUrl')) {
            $metaImageUrl = $model->getMetaImageUrl();
        } elseif ($model->hasMethod('getImageUrl')) {
            $metaImageUrl = $model->getImageUrl();
        } elseif ($model->hasProperty('imageUrl')) {
            $metaImageUrl = $model->imageUrl;
        } elseif ($model->hasMethod('getMetaImage')) {
            $metaImageUrl = $model->getMetaImage();
        } elseif ($model->hasMethod('getImage')) {
            $metaImageUrl = $model->getImage();
        } elseif ($model->hasProperty('image')) {
            $metaImageUrl = $model->image;
        }

        if (!empty($metaImageUrl)) {
            if ($model->hasMethod('getMetaImageAlt')) {
                $metaImageAlt = $model->getMetaImageAlt();
            } elseif ($model->hasMethod('getImageAlt')) {
                $metaImageAlt = $model->getImageAlt();
            } elseif ($model->hasProperty('imageAlt')) {
                $metaImageAlt = $model->imageAlt;
            } else {
                $metaImageAlt = $this->_title;
            }

            $this->registerImage($metaImageUrl, $metaImageAlt);
        }

        return $this;
    }

    /**
     * This function registers the different meta tags not with the default attributes.
     *
     * @param Yii\base\Model $model
     * @param $modelSpecificAttributes
     * @return MetaManager
     * @throws UnknownPropertyException
     */
    protected function registerMetasWithUniqueAttributes(Yii\base\Model $model, $modelSpecificAttributes)
    {
        foreach ($modelSpecificAttributes as $key => $attribute) {
            $attributeValue = null;

            if (is_callable($attribute)) {
                $attributeValue = $attribute();
            } elseif ($model->hasMethod($attribute)) {
                $attributeValue = call_user_func([$model, $attribute], $this);
            } elseif ($model->hasProperty($attribute)) {
                $attributeValue = $model->$attribute;
            }

            if (!empty($attributeValue)) {
                $localFunction = 'register' . ucfirst(strtolower($key));
                if (is_callable([$this, $localFunction])) {
                    call_user_func([$this, $localFunction], $attributeValue);
                    continue;
                }

                throw new UnknownPropertyException('The \'' . $key . '\' meta tag is unknown!');
            }
        }

        return $this;
    }
}
