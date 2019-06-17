<?php

namespace Modules\UserManagement\Services;

use Modules\UserManagement\Entities\TrackingVisit;
use Modules\UserManagement\Entities\TrackingClick;
use Modules\UserManagement\Entities\TrackingInsertEmail;
use Modules\UserManagement\Entities\TrackingInsertValue;
use Modules\UserManagement\Entities\TrackingShow;
use Modules\UserManagement\Entities\TrackingInitializeDonationInvalid;

class TrackingService
{

    public function __construct()
    {

    }

    public function createVisit($userId, $userCookie, $url, $title, $articleId)
    {
       try {
           return TrackingVisit::create([
               'user_id' => $userId,
               'user_cookie' => $userCookie,
               'url' => $url,
               'article_id' => $articleId,
               'title' => $title
           ]);
       } catch (\Exception $e) {
           dd($e->getMessage(), $e->getTrace());
       }
    }

    public function show($trackingVisitId, $widgetId)
    {
        try {
            $trackingShow = TrackingShow::create([
                'visit_id' => $trackingVisitId,
                'widget_id' => $widgetId,
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getTrace());
        }
        return $trackingShow;
    }

    public function click($data)
    {
        try {
            $trackingClick = TrackingClick::create([
                "show_id" => $data['show_id'],
                "node_class" => $data['node_class'],
                "node_id" => $data['node_id']
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getTrace());
        }
        return $trackingClick;
    }

    public function insertValue($data)
    {
        try {
            $trackingInsertValue = TrackingInsertValue::create([
                "show_id" => $data['show_id'],
                "value" => $data['value'],
                "frequency" => $data['frequency']
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getTrace());
        }
        return $trackingInsertValue;

    }

    public function insertEmail($data)
    {
        try {
            $trackingInsertValue = TrackingInsertEmail::create([
                "show_id" => $data['show_id'],
                "email" => $data['email'],
                "email_valid" => $data['email_valid']
            ]);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e->getTrace());
        }
        return $trackingInsertValue;
    }

    public function initializeDonationInvalid($data)
    {
        try {
            return TrackingInitializeDonationInvalid::create([
                'show_id' => $data['show_id'],
                'email' => $data['email'],
                'terms' => $data['terms'],
                'frequency' => $data['frequency'],
                'donation_value' => $data['donation_value']
            ]);
        } catch (\Exception $e) {
            dd($e);
        }

    }

}