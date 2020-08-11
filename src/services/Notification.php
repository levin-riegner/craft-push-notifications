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
use Sly\NotificationPusher\PushManager;
use Sly\NotificationPusher\Adapter\Gcm as GcmAdapter;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Model\Device;
use Sly\NotificationPusher\Model\Message;
use Sly\NotificationPusher\Model\Push;
use Sly\NotificationPusher\Model\PushInterface;
use Sly\NotificationPusher\Model\ResponseInterface;

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
    /** @var Client */
    private $apnsClient;

    /** @var GcmAdapter */
    private $fcmClient;

    public function __construct()
    {
        $this->initialize();
    }

    private function initialize() : void
    {

        if(CraftPushNotifications::$plugin->getSettings()->apnsEnabled){
            $apnsAuthType = CraftPushNotifications::$plugin->getSettings()->apnsAuthType;
            if($apnsAuthType === 'token'){
                $options = [
                    'key_id' => CraftPushNotifications::$plugin->getSettings()->getApnsKeyId(), // The Key ID obtained from Apple developer account
                    'team_id' => CraftPushNotifications::$plugin->getSettings()->getApnsTeamId(), // The Team ID obtained from Apple developer account
                    'app_bundle_id' => CraftPushNotifications::$plugin->getSettings()->getApnsBundleId(), // The bundle ID for app obtained from Apple developer account
                    'private_key_path' => CraftPushNotifications::$plugin->getSettings()->getApnsTokenKeyPath(), // Path to private key
                    'private_key_secret' => CraftPushNotifications::$plugin->getSettings()->getApnsTokenKeySecret() // Private key secret
                ];

                $this->authProvider = AuthProvider\Token::create($options);
            }else if($apnsAuthType === 'certificate'){
                $options = [
                    'app_bundle_id' => CraftPushNotifications::$plugin->getSettings()->getApnsBundleId(), // The bundle ID for app obtained from Apple developer account
                    'certificate_path' => CraftPushNotifications::$plugin->getSettings()->getApnsKeyPath(), // Path to private key
                    'certificate_secret' => CraftPushNotifications::$plugin->getSettings()->getApnsKeySecret() // Private key secret
                ];

                $this->authProvider = AuthProvider\Certificate::create($options);
            }

            //TODO make the production flag dynamic (maybe based on the current environment?)
            $this->apnsClient = new Client($this->authProvider, $production = false);
        }

        if(CraftPushNotifications::$plugin->getSettings()->fcmEnabled){
            $fcmApiKey = CraftPushNotifications::$plugin->getSettings()->getFcmApiKey();

            $this->fcmClient = new GcmAdapter(array(
                'apiKey' => $fcmApiKey,
            ));
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
        
        $apnsEnabled = CraftPushNotifications::$plugin->settings->apnsEnabled;
        if(!$apnsEnabled)
            return [];

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
        
        $this->apnsClient->addNotifications($notifications);
        
        $responses = $this->apnsClient->push(); // returns an array of ApnsResponseInterface (one Response per Notification)

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
        $fcmEnabled = CraftPushNotifications::$plugin->settings->fcmEnabled;
        if(!$fcmEnabled)
            return [];
        
        $devices = new DeviceCollection(array_map(function($installation) {
                return new Device($installation->token);
            }, $installations)
        );

        $params = array(
            'notificationData' => array('title' => $notification->title, 'body' => $notification->text, 'sound' => $notification->sound), 
            //'android' => array('notification_count' => $notification->badge), 
            'data' => $notification->metadata
        );

        $message = new Message($notification->text, $params);
        $pushManager = new PushManager(PushManager::ENVIRONMENT_DEV);

        $push = new Push($this->fcmClient, $devices, $message);
        $pushManager->add($push);
        
        $responses = $pushManager->push(); // Returns a collection of notified devices
        
        $results = array();
        /** @var PushInterface */
        foreach($responses as $response){
            foreach($response->getResponses() as $token => $deviceResponse){
                array_push($results, [
                    'fcmId'=>$token,
                    'statuscode'=>array_key_exists('error', $deviceResponse) ? $deviceResponse['error']: 'OK',
                ]);
            }
            
        }

        return $results;
    }
}
