<?php

namespace Modules\Campaigns\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends Model
{
    use SoftDeletes;
    protected $table = 'widgets';
    protected $fillable = [
        'widget_type_id', 'campaign_id', 'active', 'use_campaign_settings'
    ];

    public function widgetSettings()
    {
        return $this->hasOne('\Modules\Campaigns\Entities\WidgetSettings');
    }

    public function campaignImage()
    {
        return $this->hasMany('\Modules\Campaigns\Entities\CampaignImage');
    }

}
