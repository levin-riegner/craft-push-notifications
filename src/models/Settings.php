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
    
    /** @var bool */
    public $apnsEnabled = false;

    /** @var string */
    public $apnsAuthType = '';

    /** @var string */
    public $apnsKeyId = '';
    /** @var string */
    public $apnsTeamId = '';
    /** @var string */
    public $apnsBundleId = '';
    /** @var string */
    public $apnsKeyPath = '';
    /** @var string */
    public $apnsKeySecret = '';

    /** @var string */
    public $apnsTokenKeyPath = '';
    /** @var string */
    public $apnsTokenKeySecret = '';

    /** @var bool */
    public $fcmEnabled = false;
    /** @var string */
    public $fcmApiKey = '';

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
            [['apnsEnabled', 'fcmEnabled'], 'boolean'],

            ['apnsAuthType', 'string'],
            ['apnsKeyId', 'string'],
            ['apnsKeyId', 'default', 'value' => ''],
            ['apnsTeamId', 'string'],
            ['apnsTeamId', 'default', 'value' => ''],
            ['apnsBundleId', 'string'],
            ['apnsBundleId', 'default', 'value' => ''],
            ['apnsKeyPath', 'string'],
            ['apnsKeyPath', 'default', 'value' => ''],
            ['apnsKeySecret', 'string'],
            ['apnsKeySecret', 'default', 'value' => ''],

            ['apnsTokenKeyPath', 'string'],
            ['apnsTokenKeyPath', 'default', 'value' => ''],
            ['apnsTokenKeySecret', 'string'],
            ['apnsTokenKeySecret', 'default', 'value' => ''],

            ['fcmApiKey', 'string'],
            ['fcmApiKey', 'default', 'value' => ''],
        ];
    }
}
