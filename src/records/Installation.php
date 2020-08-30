<?php
/**
 * Craft push notifications plugin for Craft CMS 3.x
 *
 * Enable sending push notifications from Craft
 *
 * @link      https://levinriegner.com
 * @copyright Copyright (c) 2019 Levinriegner
 */

namespace levinriegner\craftpushnotifications\records;

use levinriegner\craftpushnotifications\CraftPushNotifications;

use Craft;
use craft\db\ActiveRecord;
use levinriegner\craftpushnotifications\models\validators\EitherValidator;
use yii\db\ActiveQueryInterface;

/**
 * Installation Record
 *
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 *
 * Active Record implements the [Active Record design pattern](http://en.wikipedia.org/wiki/Active_record).
 * The premise behind Active Record is that an individual [[ActiveRecord]] object is associated with a specific
 * row in a database table. The object's attributes are mapped to the columns of the corresponding table.
 * Referencing an Active Record attribute is equivalent to accessing the corresponding table column for that record.
 *
 * http://www.yiiframework.com/doc-2.0/guide-db-active-record.html
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 * 
 * @property integer $userId
 * @property string $deviceToken
 */
class Installation extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

     /**
     * Declares the name of the database table associated with this AR class.
     * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
     * with prefix [[Connection::tablePrefix]]. For example if [[Connection::tablePrefix]] is `tbl_`,
     * `Customer` becomes `tbl_customer`, and `OrderItem` becomes `tbl_order_item`. You may override this method
     * if the table is not named after this convention.
     *
     * By convention, tables created by plugins should be prefixed with the plugin
     * name and an underscore.
     *
     * @return string the table name
     */
    public static function tableName()
    {
        return '{{%craftpushnotifications_installations}}';
    }

    public function getTopics()
    {
        return $this->hasMany(Topic::class, ['id' => 'topic_id'])->viaTable('craft_craftpushnotifications_installations_topics_assn', ['installation_id' => 'id']);
    }

    public function getUser(): ActiveQueryInterface
    {
        return $this->hasOne(CustomUser::class, ['id' => 'userId']);
    }

    public function rules()
    { 
        // only fields in rules() are searchable
        return [
            [['deviceType'], 'in', 'range' => ['android','ios']],
            [['appName','appIdentifier','appVersion'], 'string'],
            [['apnsToken','fcmToken','deviceType'], 'string'],
            [['locale','timeZone','osVersion'], 'string'],
            [['locationLat','locationLon','locationAuthStatus'], 'number'],
            [['deviceType', 'appIdentifier'], 'required'],
            [['apnsToken'], 'apns_validator'],
            [['apnsToken', 'fcmToken'], EitherValidator::class, 'skipOnEmpty' => false],
            [['topicNames'], 'safe'],
        ];
    }

    public function apns_validator($attr_name)
    {
        if($this->deviceType === 'android'){
            $this->addError(
                $attr_name, 
                'This field cannot be set if deviceType is android'
            );

            return false;
        }

        return true;
    }

    public function behaviors() {
        return [
            [
                'class' => Taggable::class,
                'relation' => 'topics',
                'attribute' => 'topicNames',
                'removeUnusedTags' => 'true'
            ],
        ];
    }
}
