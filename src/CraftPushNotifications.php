<?php
/**
 * Craft push notifications plugin for Craft CMS 3.x
 *
 * Enable sending push notifications from Craft
 *
 * @link      https://levinriegner.com
 * @copyright Copyright (c) 2019 Levinriegner
 */

namespace levinriegner\craftpushnotifications;

use chasegiunta\jason\fields\JasonField;
use levinriegner\craftpushnotifications\services\Notification as NotificationService;
use levinriegner\craftpushnotifications\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\events\RegisterUrlRulesEvent;
use craft\fields\Dropdown;
use craft\fields\PlainText;
use craft\fields\Users;
use craft\helpers\ElementHelper;
use craft\models\EntryType;
use craft\models\FieldGroup;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use levinriegner\craftpushnotifications\models\InstallationModel;
use levinriegner\craftpushnotifications\models\NotificationModel;
use levinriegner\craftpushnotifications\records\Installation;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Levinriegner
 * @package   CraftPushNotifications
 * @since     0.1.0
 *
 * @property  NotificationService $notification
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class CraftPushNotifications extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * CraftPushNotifications::$plugin
     *
     * @var CraftPushNotifications
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '0.1.0';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * CraftPushNotifications::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Register our site routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'craft-push-notifications/notification';
            }
        );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'craft-push-notifications/notification/do-something';
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // We were just installed
                }
            }
        );

        Event::on(
            Entry::class,
            Entry::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                if ($event->sender instanceof Entry) {
                    /** @var Entry $entry */
                    $entry = $event->sender;

                    if(ElementHelper::isDraftOrRevision($entry)){
                        return;
                    }

                    if($entry->section->handle === 'notification' && $event->isNew){
                        $notification = new NotificationModel();
                        $notification->title = $entry->title;
                        $notification->text = $entry->getFieldValue('notifDescription');

                        $installations = [];
                        if($entry->type->handle === 'manual'){
                            /** @var User $user */
                            foreach($entry->getFieldValue('notifUsers')->all() as $user){
                                $installations = array_merge($installations, Installation::find()->where('userId='.$user->id)->all());
                            }
                        }else if($entry->type->handle === 'automatic'){
                            if($entry->getFieldValue('notifDestination')->value === 'allUsers'){
                                $installations = Installation::find()->all();
                            }else if($entry->getFieldValue('notifDestination')->value === 'loggedUsers'){
                                $installations = Installation::find()->where('userId is not null')->all();
                            }
                        }

                        $installationModels = InstallationModel::createFromRecords($installations);
                        $results = $this->notification->sendNotification($notification, $installationModels);

                        $entry->setFieldValue('notifResults', json_encode($results));
                        
                        Craft::$app->getElements()->saveElement($entry);
                    }
                }
            }
        );

/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        Craft::info(
            Craft::t(
                'craft-push-notifications',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'craft-push-notifications/settings',
            [
                'settings' => $this->getSettings(),
                'authTypes' => array(
                                    ['label' =>'Token based', 'value'=>'token'], 
                                    ['label' =>'Certificate based', 'value'=>'certificate']
                                )
            ]
        );
    }

    protected function afterInstall()
    {
        // Create the field group
        $groupModel = $this->createFieldGroup('Notification');

        $descrField = new PlainText();
        $descrField->groupId      = $groupModel->id;
        $descrField->name         = 'Description';
        $descrField->handle       = 'notifDescription';
        $descrField->multiline    = true;

        $destField = new Dropdown();
        $destField->groupId      = $groupModel->id;
        $destField->name         = 'Destination';
        $destField->handle       = 'notifDestination';
        $destField->options[] = [
            'label' => 'Logged users',
            'value' => 'loggedUsers',
            'default' => 'true'
        ];

        $destField->options[] = [
            'label' => 'All users',
            'value' => 'allUsers',
            'default' => ''
        ];

        $userField = new Users();
        $userField->groupId      = $groupModel->id;
        $userField->name         = 'Users';
        $userField->handle       = 'notifUsers';

        $responseField = new JasonField();
        $responseField->groupId     = $groupModel->id;
        $responseField->name        = 'Results';
        $responseField->handle      = 'notifResults';
        $responseField->allowRawEditing = 'false';
        $responseField->readonly      = 'true';

        Craft::$app->fields->saveField($descrField);
        Craft::$app->fields->saveField($destField);
        Craft::$app->fields->saveField($userField);
        Craft::warning(Craft::$app->fields->saveField($responseField));

        $section = new Section();        
        $section->name = "Notification";
        $section->handle = "notification";
        $section->type = Section::TYPE_CHANNEL;
        $section->siteSettings =  [
            new Section_SiteSettings([
                'siteId' => Craft::$app->sites->getPrimarySite()->id,
                'enabledByDefault' => true,
                'hasUrls' => true,
                'uriFormat' => 'notification/{slug}',
                'template' => '',
            ]),
        ];

        Craft::$app->sections->saveSection($section);

        $this->createEntryType('Manual', 'manual', $section->id, [$descrField, $userField, $responseField]);
        $this->createEntryType('Automatic', 'automatic', $section->id, [$descrField, $destField, $responseField]);

        $entryType = Craft::$app->getSections()->getEntryTypesBySectionId($section->id)[0];
        Craft::$app->sections->deleteEntryType($entryType);

    }

    protected function beforeUninstall(): bool
    {
        $group = $this->createFieldGroup('Notification');
        Craft::$app->fields->deleteGroup($group);

        $section = Craft::$app->sections->getSectionByHandle('notification');
        Craft::$app->sections->deleteSection($section);

        return true;
    }

    private function createEntryType($name, $handle, $sectionId, $fields){
        $entryType = new EntryType();
        $entryType->sectionId = $sectionId;
        $entryType->name = $name;
        $entryType->handle = $handle;
        
        $entryType->hasTitleField = true;
        $entryType->titleLabel = Craft::t('app', 'Title');
        $entryType->titleFormat = null;

        $fieldLayout = new FieldLayout();

        $fieldLayoutTab = new FieldLayoutTab();
        $fieldLayoutTab->name = $name;
        $fieldLayoutTab->setFields($fields);

        $fieldLayout->setTabs([$fieldLayoutTab]);

        $entryType->setFieldLayout($fieldLayout);

        Craft::$app->sections->saveEntryType($entryType);

        return $entryType;
    }

    private function createFieldGroup($name): FieldGroup{
        // Create the field group
        $groupModel = new FieldGroup();
        $groupModel->name = $name;
        Craft::$app->fields->saveGroup($groupModel);

        if($groupModel->id === null){
            $groups = Craft::$app->fields->getAllGroups();
            foreach($groups as $group) {
                if($group->name != $name) {
                    continue;
                }
                $groupModel = $group;
            }
        }

        return $groupModel;
    }
}
