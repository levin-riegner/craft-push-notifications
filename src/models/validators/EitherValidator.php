<?php

namespace levinriegner\craftpushnotifications\models\validators;

use Craft;
use yii\validators\Validator;

class EitherValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttributes($model, $attributes = null)
    {
        $values = [];
        $attributes = $this->attributes;
        foreach($attributes as $attribute) {
            if(!empty($model->$attribute)) {
                $values[] = $model->$attribute;
            }
        }

        if (count($values) != 1) {
            $labels = implode(', ', $attributes);
            foreach($attributes as $attribute) {
                $this->addError($model, $attribute, "Fill one, and only one of the following fields: {$labels}.");
            }
            return false;
        }
        return true;
    }
}