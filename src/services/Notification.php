<?php
/**
 * Craft push notifications plugin for Craft CMS 3.x
 *
 * Enable sending push notifications from Craft
 *
 * @link      https://levinriegner.com
 * @copyright Copyright (c) 2019 Levinriegner
 */

namespace levinriegner\craftpushnotifications\services;

use levinriegner\craftpushnotifications\CraftPushNotifications;

use Craft;
use craft\base\Component;
use levinriegner\craftpushnotifications\records\Installation;
use Sly\NotificationPusher\Adapter\Apns;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\Model\Push;
use Sly\NotificationPusher\PushManager;

/**
 * Notification Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 */
class Notification extends Component
{

    private $pemFile;
    private $pemPass;

    private $apnsAdapter;

    public function __construct()
    {
        $this->pemFile = CraftPushNotifications::$plugin->getSettings()->pemFile;
        $this->pemPass = CraftPushNotifications::$plugin->getSettings()->pemPass;

        $this->initialize();
    }

    private function initialize() : void
    {
        // Then declare an adapter.
        $this->apnsAdapter = new Apns(array(
            'certificate' => $this->pemFile,
        ));
        
    }
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     CraftPushNotifications::$plugin->notification->exampleService()
     *
     * @return mixed
     */
    public function sendNotification($user_id, $message)
    {
        //Get device tokens from installations
        $pushManager = new PushManager(PushManager::ENVIRONMENT_DEV);
        $installations = Installation::find()->where(['userId' => $user_id])->all();
        
        $devices = array();
        foreach($installations as $installation)
            array_push($devices, new Device($installation->deviceToken));

        $devices = new DeviceCollection($devices);
        $push = new Push($this->apnsAdapter, $devices, $message);
        $pushManager->add($push);
        $pushManager->push();

        $results = array();

        foreach($push->getResponses() as $token => $response)
            $results[$token] = $response;

        return $results;
    }
}
