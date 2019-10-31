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

use levinriegner\craftpushnotifications\CraftPushNotifications;

use Craft;
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
class Notification extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $text = '';
    
    /**
     * @var string
     */
    public $badge = '';
    
    /**
     * @var string
     */
    public $sound = '';
    
    /**
     * @var array
     */
    public $metadata = array();

}