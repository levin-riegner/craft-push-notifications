<?php

namespace levinriegner\craftpushnotifications\models;

use craft\base\Model;

class InstallationModel extends Model
{
    public $token;
    public $type;
    public $deviceType;

    public function rules()
    {
        return [
            [['token', 'type'], 'string'],
            [['token', 'type'], 'required'],
            [['type'], 'in', 'range' => ['fcm','apns']],
        ];
    }

    public static function createFromRecords($installations)
    {
        return array_map(function ($installation){
            return self::createFromRecord($installation);
        }, $installations);
    }

    public static function createFromRecord($installation)
    {
            $installationModel = new InstallationModel();
            $installationModel->deviceType = $installation->deviceType;
            if(!empty($installation->apnsToken)){
                $installationModel->type === 'apns';
                $installationModel->token = $installation->apnsToken;
            }else{
                $installationModel->type === 'fcm';
                $installationModel->token = $installation->fcmToken;
            }

            return $installationModel;
    }
}