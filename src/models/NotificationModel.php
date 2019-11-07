<?php
/**
 * Craft push notifications plugin for Craft CMS 3.x
 *
 * Enable sending push notifications from Craft
 *
 * @link      https://levinriegner.com
 * @copyright Copyright (c) 2019 Levinriegner
 */

namespace levinriegner\craftpushnotifications\models;

use craft\base\Model;

/**
 * Notification Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 */
class NotificationModel extends Model
{
    // Public Properties
    // =========================================================================

/**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $text;
    
    /**
     * @var int
     */
    public $badge;
    
    /**
     * @var string
     */
    public $sound ;
    
/**
     * @var bool
     */
    public $available = false;

    /**
     * @var array
     */
    public $metadata = array();

    public function attributes()
    {
        return ['title', 'text', 'badge', 'sound', 'available', 'metadata'];
    }
}