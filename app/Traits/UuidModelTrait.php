<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

trait UuidModelTrait {
    /*
     * This function is used internally by Eloquent models to test if the model has auto increment value
     * @returns bool Always false
     */
    public function getIncrementing(){
        return false;
    }

    /**
     * This function overwrites the default boot static method of Eloquent models. It will hook
     * the creation event with a simple closure to insert the UUID
     */
    public static function bootUuidModelTrait(){
        static::creating(function ($model) {
            if (!isset($model->attributes[$model->getKeyName()])) {
                $model->incrementing = false;
                $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
                $model->attributes[$model->getKeyName()] = $uuid;
            }
        }, 0);
    }
}
