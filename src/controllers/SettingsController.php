<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace levinriegner\craftpushnotifications\controllers;

use Craft;
use craft\base\ComponentInterface;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use levinriegner\craftpushnotifications\CraftPushNotifications;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Edit the plugin settings.
     *
     * @return Response|null
     */
    public function actionEdit()
    {
        $settings = CraftPushNotifications::$plugin->settings;

        return $this->renderTemplate('craft-push-notifications/_settings', [
            'settings' => $settings,
            'authTypes' => array(
                ['label' =>'Token based', 'value'=>'token'], 
                ['label' =>'Certificate based', 'value'=>'certificate']
            ),
        ]);
    }

    /**
     * Saves the plugin settings.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $postedSettings = $request->getBodyParam('settings', []);

        $settings = CraftPushNotifications::$plugin->settings;
        $settings->setAttributes($postedSettings, false);

        // Validate
        $settings->validate();

        if ($settings->hasErrors()
        ) {
            Craft::$app->getSession()->setError(Craft::t('craft-push-notifications', 'Couldn’t save plugin settings.'));

            return null;
        }

        // Save it
        Craft::$app->getPlugins()->savePluginSettings(CraftPushNotifications::$plugin, $settings->getAttributes());

        $notice = Craft::t('craft-push-notifications', 'Plugin settings saved.');
        $errors = [];

        if (!empty($errors)) {
            Craft::$app->getSession()->setError($notice.' '.implode(' ', $errors));

            return null;
        }

        Craft::$app->getSession()->setNotice($notice);

        return $this->redirectToPostedUrl();
    }
}