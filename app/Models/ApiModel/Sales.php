<?php

namespace App\Models\ApiModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;

    public function plans(){
        return  $this->BelongsTo(Plan::class, 'plan_id', 'id');
    }

    public function allowed_searches(){
        return $this->hasOne(AllowedSearch::class, 'sale_id', 'id');
    }
}
