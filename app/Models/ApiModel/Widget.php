<?php

namespace App\Models\ApiModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use HasFactory;

    public function votes(){
        return $this->hasMany(WidgetsVote::class, 'widget_id', 'id');
    }
}
