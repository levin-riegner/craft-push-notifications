<?php

namespace levinriegner\craftpushnotifications\models;

use craft\base\Model;

class InstallationModel extends Model
{
    public $token;
    public $type;


    public static function createFromRecords($installations)
    {
        return array_map(function ($installation){
            $installationModel = new InstallationModel();
            $installationModel->type = $installation->deviceType;
            if($installationModel->type === 'apns')
                $installationModel->token = $installation->apnsToken;
            else if($installationModel->type === 'fcm')
                $installationModel->token = $installation->fcmToken;

            return $installationModel;
        }, $installations);
    }
}