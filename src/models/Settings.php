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
 * Settings Model
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
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some model attribute
     *
     * @var string
     */
    public $pemFile = '';
    public $pemPass = '';

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return [
            ['pemFile', 'string'],
            ['pemFile', 'default', 'value' => ''],
            ['pemPass', 'string'],
            ['pemPass', 'default', 'value' => ''],
        ];
    }
}
