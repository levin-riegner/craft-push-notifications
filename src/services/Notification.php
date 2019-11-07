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
use levinriegner\craftpushnotifications\models\InstallationModel;
use levinriegner\craftpushnotifications\models\NotificationModel;
use levinriegner\craftpushnotifications\records\Installation;
use Pushok\AuthProvider;
use Pushok\Client;
use Pushok\Notification as PushokNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;

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
    private $authProvider;

    public function __construct()
    {
        $this->initialize();
    }

    private function initialize() : void
    {
        $apnsAuthType = CraftPushNotifications::$plugin->getSettings()->apnsAuthType;
        if($apnsAuthType === 'token'){
            $options = [
                'key_id' => CraftPushNotifications::$plugin->getSettings()->apnsKeyId, // The Key ID obtained from Apple developer account
                'team_id' => CraftPushNotifications::$plugin->getSettings()->apnsTeamId, // The Team ID obtained from Apple developer account
                'app_bundle_id' => CraftPushNotifications::$plugin->getSettings()->apnsBundleId, // The bundle ID for app obtained from Apple developer account
                'private_key_path' => CraftPushNotifications::$plugin->getSettings()->apnsTokenKeyPath, // Path to private key
                'private_key_secret' => CraftPushNotifications::$plugin->getSettings()->apnsTokenKeySecret // Private key secret
            ];

            $this->authProvider = AuthProvider\Token::create($options);
        }else if($apnsAuthType === 'certificate'){
            $options = [
                'app_bundle_id' => CraftPushNotifications::$plugin->getSettings()->apnsBundleId, // The bundle ID for app obtained from Apple developer account
                'certificate_path' => CraftPushNotifications::$plugin->getSettings()->apnsKeyPath, // Path to private key
                'certificate_secret' => CraftPushNotifications::$plugin->getSettings()->apnsKeySecret // Private key secret
            ];

            $this->authProvider = AuthProvider\Certificate::create($options);
        }
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
     * @param InstallationModel[] $installations
     * @return mixed
     */
    public function sendNotification(NotificationModel $notification, array $installations)
    {
        $apnsInstallations  = array();
        $fcmInstallations   = array();
        foreach ($installations as $installation) {
            if($installation->type === 'apns'){
                array_push($apnsInstallations, $installation);
            }else if($installation->type === 'fcm'){
                array_push($fcmInstallations, $installation);
            }
        }
        $results = array();

        $results = array_merge($results, $this->sendFcmNotification($notification, $fcmInstallations));
        $results = array_merge($results, $this->sendApnsNotification($notification, $apnsInstallations));

        return $results;
    }

    /**
     * * @param InstallationModel[] $installations
     */
    private function sendApnsNotification(NotificationModel $notification, array $installations){
        
        $alert = null;
        if(is_string($notification->title) || is_string($notification->text))
            $alert = Alert::create()
                            ->setTitle($notification->title)
                            ->setBody($notification->text);
                
        $payload = Payload::create();
        if($notification->available === true)
            $payload->setContentAvailability($notification->available);
        
        if($alert !== null)
            $payload->setAlert($alert);
        if(is_string($notification->sound))
            $payload->setSound($notification->sound);
        if(is_int($notification->badge))
            $payload->setBadge($notification->badge);


        foreach ($notification->metadata as $clave => $valor){
            $payload->setCustomValue($clave, $valor);
        }
                
        $notifications = [];
        foreach ($installations as $installation) {
            array_push($notifications, new PushokNotification($payload,$installation->token));
        }
        
        $client = new Client($this->authProvider, $production = false);
        $client->addNotifications($notifications);
        
        $responses = $client->push(); // returns an array of ApnsResponseInterface (one Response per Notification)

        $results = array();

        foreach ($responses as $response) {
            array_push($results, [
                'apnsId'=>$response->getApnsId(),
                'statuscode'=>$response->getStatusCode(),
                'reasonPhrase'=>$response->getReasonPhrase(),
                'errorReason'=>$response->getErrorReason(),
                'errorDescription'=>$response->getErrorDescription()
            ]);
            
        }

        return $results;
    }

    /**
     * * @param InstallationModel[] $installations
     */
    private function sendFcmNotification(NotificationModel $notification, array $installations){
        return [];
    }
}
