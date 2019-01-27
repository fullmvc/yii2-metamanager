<?php

namespace fullmvc\metamanager;

/**
 * You can use this interface to ease of use the MetaManager with model specific getters.
 *
 * Interface MetaManagerInterface
 * @package fullmvc\metamanager
 */
interface MetaManagerInterface
{
    /**
     * Getter method for the meta title tags.
     * @return string
     */
    public function getMetaTitle();

    /**
     * Getter method for the Description tags.
     *
     * @return string
     */
    public function getMetaDescription();

    /**
     * Getter for meta image tags.
     *
     * @return string Any relative url that leads to an image
     */
    public function getMetaImageUrl();

    /**
     * Getter for meta image tags' alt text.
     *
     * @return string return the alt text of the meta image if it is not accessible
     */
    public function getMetaImageAlt();

    /**
     * Getter for the meta keywords. When you don't want to use keywords, declare
     * it something like this:
     *
     * ```php
     * public function getMetaKeywords() {
     *      return '';
     * }
     * ```
     *
     * @return string
     */
    public function getMetaKeywords();
}