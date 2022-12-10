<?php

namespace App\Models\ApiModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function sales(){
        return  $this->hasOne(Sales::class);
    }

}
