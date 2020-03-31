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
use craft\records\User;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;

/**
 * CustomUser record
 *
 * We need to redefine how the find query is built because by default 
 * craft creates an inner join with the element table. It needs to be 
 * a left join in order to be chainable with other left joins
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 * 
 */
class CustomUser extends User
{
    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        $query = Yii::createObject(ActiveQuery::className(), [CustomUser::class])
            ->joinWith(['element element']);

        // todo: remove schema version condition after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            $query->where(['element.dateDeleted' => null]);
        }

        return $query;

    }
}
