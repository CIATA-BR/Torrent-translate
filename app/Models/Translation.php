<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Translation extends Model {
    protected $fillable = ['translation_source_id','locale_id','updated_by','text','status'];
}
