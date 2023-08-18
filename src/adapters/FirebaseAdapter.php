<?php

namespace levinriegner\craftpushnotifications\adapters;

use InvalidArgumentException;
use Sly\NotificationPusher\Collection\DeviceCollection;
use Sly\NotificationPusher\Exception\PushException;
use Sly\NotificationPusher\Model\DeviceInterface;
use Sly\NotificationPusher\Model\GcmMessage;
use Sly\NotificationPusher\Model\MessageInterface;
use Sly\NotificationPusher\Model\PushInterface;
use Laminas\Http\Client as HttpClient;
use Sly\NotificationPusher\Adapter\BaseAdapter;
use ZendService\Google\Exception\RuntimeException as ServiceRuntimeException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\SendReport;
use levinriegner\craftpushnotifications\CraftPushNotifications;
use Sly\NotificationPusher\Model\BaseOptionedModel;

/**
 * @uses BaseAdapter
 *
 * @author Cédric Dugat <cedric@dugat.me>
 */
class FirebaseAdapter extends BaseAdapter
{
    /**
     * @var ServiceClient
     */
    private Messaging $messaging;

    /**
     * {@inheritdoc}
     */
    public function supports(string $token): bool
    {
        return !empty($token);
    }

    /**
     * {@inheritdoc}
     *
     * @throws PushException
     */
    public function push(PushInterface $push): DeviceCollection
    {
        $client = $this->getOpenedClient();
        $pushedDevices = new DeviceCollection();
        $tokens = array_chunk($push->getDevices()->getTokens(), 100);

        $message = $this->getServiceMessageFromOrigin($push->getMessage());

        foreach ($tokens as $tokensRange) {
            
            try {

                $response = $client->sendMulticast($message, $tokensRange);

                foreach ($tokensRange as $token) {
                    /** @var DeviceInterface $device */
                    $device = $push->getDevices()->get($token);

                    // map the overall response object
                    // into a per device response
                    $tokenResponse = [];
                    $responseItem = current($response->filter(fn(SendReport $report) => $report->target()->value() == $token)->getItems());
                 
                    if ($responseItem) {
                        $tokenResponse = $responseItem;
                    }

                    //TODO Check if we need to add the 'error' key with the error message
                    $push->addResponse($device, $tokenResponse);

                    $pushedDevices->add($device);

                    $this->response->addOriginalResponse($device, $responseItem);
                    $this->response->addParsedResponse($device, $responseItem->result() ?: []);
                }
            } catch (ServiceRuntimeException $e) {
                throw new PushException($e->getMessage());
            }
        }

        return $pushedDevices;
    }

    /**
     * Get opened client.
     *
     * @return Messaging
     */
    public function getOpenedClient(): Messaging
    {
        if (!isset($this->messaging)) {
            $factory = (new Factory)->withServiceAccount($this->getParameter('firebaseCredentials'));
            $this->messaging = $factory->createMessaging();
        }

        return $this->messaging;
    }

    /**
     * Get service message from origin.
     *
     * @param array $tokens Tokens
     * @param MessageInterface $message Message
     *
     * @return CloudMessage
     */
    public function getServiceMessageFromOrigin(MessageInterface $message): CloudMessage
    {
        $data = $message->getOptions();
        $data['message'] = $message->getText();

        $serviceMessage = CloudMessage::new();
        $androidConfig = AndroidConfig::new()
            ->fromArray([
                'ttl' => $this->getParameter('ttl', 600),
                'collapse_key' => $this->getParameter('collapseKey'),
                'restricted_package_name' => $this->getParameter('restrictedPackageName'),
            ])
            ->withMessagePriority($this->getParameter('priority', 'normal'))
        ;

        if (isset($data['notificationData']) && !empty($data['notificationData'])) {
            $serviceMessage = $serviceMessage->withNotification($data['notificationData']);
            $androidConfig = $androidConfig->withSound($data['notificationData']['sound'] ?: 'default');

            unset($data['notificationData']);
        }

        $serviceMessage = $serviceMessage
            ->withData($data['data'])
            ->withAndroidConfig($androidConfig);

        return $serviceMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinedParameters(): array
    {
        return [
            'collapseKey',
            'priority',
            'delayWhileIdle',
            'ttl',
            'restrictedPackageName',
            'dryRun',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParameters(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredParameters(): array
    {
        return ['firebaseCredentials'];
    }
}
