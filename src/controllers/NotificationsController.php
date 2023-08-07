<?php
/**
 * Craft push notifications plugin for Craft CMS 3.x
 *
 * Enable sending push notifications from Craft
 *
 * @link      https://levinriegner.com
 * @copyright Copyright (c) 2019 Levinriegner
 */

namespace levinriegner\craftpushnotifications\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use levinriegner\craftpushnotifications\CraftPushNotifications;
use levinriegner\craftpushnotifications\models\InstallationModel;
use levinriegner\craftpushnotifications\models\NotificationModel;

/**
 * Notification Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 */
class NotificationsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|int|bool $allowAnonymous = false;

    public function beforeAction($action): bool
	{

        $this->enableCsrfValidation = false;

		return parent::beforeAction($action);
	}

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/craft-push-notifications/notification
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $post = Craft::$app->getRequest()->getRawBody();
        $data = Json::decode($post, true);
        $installations = array();
        $notification = new NotificationModel();
        $notification->setAttributes($data['notification']);
        if(!$notification->validate()){
            Craft::$app->getResponse()->setStatusCode(400);
            return $this->asJson(['errors' => ['notification'=> $notification->getErrors()]]);
        }

        foreach($data['installations'] as $installationData){
            $installation = new InstallationModel();
            $installation->setAttributes($installationData);
            if(!$installation->validate()){
                Craft::$app->getResponse()->setStatusCode(400);
                return $this->asJson(['errors' => ['installations'=> $installation->getErrors()]]);
            }

            $installations[] = $installation;
        }

        $resp = CraftPushNotifications::getInstance()->notification->sendNotification($notification, $installations);
        
        return $this->asJson($resp);
    }
}
