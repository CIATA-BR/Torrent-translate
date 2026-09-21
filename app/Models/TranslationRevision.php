<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TranslationRevision extends Model {
    protected $fillable = ['translation_id','user_id','old_text','new_text','old_status','new_status'];
}
