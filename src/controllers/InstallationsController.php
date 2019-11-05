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
use craft\web\Controller;
use levinriegner\craftpushnotifications\records\Installation;
use yii\data\ActiveDataFilter;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;

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
class InstallationsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = false;

    public function beforeAction($action)
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
    public function actionSave()
    {
        $result = 'Welcome to the NotificationController actionTokens() method';

        return $result;
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/craft-push-notifications/notification/do-something
     *
     * @return mixed
     */
    public function actionSearch()
    {
        $filter = new ActiveDataFilter([
            'searchModel' => 'levinriegner\craftpushnotifications\records\Installation'
        ]);
        
        $filterCondition = null;
        
        // You may load filters from any source. For example,
        // if you prefer JSON in request body,
        // use Yii::$app->request->getBodyParams() below:
        if ($filter->load(\Yii::$app->request->get())) { 
            $filterCondition = $filter->build(false);
        }
        
        $query = Installation::find();
        if($filterCondition)
            $query->andWhere($filterCondition);
        else
            $query->where("false");
        
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Craft::$app->getRequest()->getParam('pageSize'),
                'page' => Craft::$app->getRequest()->getParam('page')
            ],
            'sort' => [
                'defaultOrder' => [
                    'appName' => SORT_DESC
                ]
            ],
        ]);

        return $this->asJson([
            'data' => $provider->getModels(), 
            'pagination' => 
            [
                'currentPage' => $provider->getPagination()->page,
                'pageSize' => $provider->getPagination()->pageSize,
                'numPages' => $provider->getPagination()->pageCount,
                'numResults' => $provider->getPagination()->totalCount
            ]
        ]);
    }
}
