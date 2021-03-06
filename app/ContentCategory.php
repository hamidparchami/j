<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentCategory extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'catalog_id', 'parent_id', 'image', 'is_active', 'is_important',
    ];

    public function contents()
    {
        return $this->hasMany('App\Content', 'category_id');
    }
}
