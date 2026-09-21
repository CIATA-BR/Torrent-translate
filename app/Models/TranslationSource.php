<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TranslationSource extends Model {
    protected $fillable = ['msgid','context','source_hash','active'];
    public function translations(): HasMany { return $this->hasMany(Translation::class); }
}
