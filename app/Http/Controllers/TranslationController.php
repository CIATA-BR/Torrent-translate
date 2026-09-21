<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\TranslationRevision;
use App\Models\TranslationSource;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $user=$request->user();
        abort_unless($user?->locale_id,403);
        $filter=$request->string('filter')->toString();
        $search=trim($request->string('q')->toString());

        $query=TranslationSource::query()->where('active',true)
            ->with(['translations'=>fn($q)=>$q->where('locale_id',$user->locale_id)]);

        if($search!=='') $query->where('msgid','like','%'.$search.'%');
        if($filter==='untranslated'){
            $query->whereDoesntHave('translations',fn($q)=>$q->where('locale_id',$user->locale_id)->whereNotNull('text')->where('text','<>',''));
        }

        return view('translations.index',[
            'sources'=>$query->paginate(30)->withQueryString(),
            'filter'=>$filter,
            'search'=>$search,
        ]);
    }

    public function store(Request $request, TranslationSource $source)
    {
        $user=$request->user();
        abort_unless($user?->locale_id,403);
        $data=$request->validate(['text'=>['nullable','string'],'status'=>['required','in:draft,review,approved']]);

        $translation=Translation::firstOrNew([
            'translation_source_id'=>$source->id,
            'locale_id'=>$user->locale_id,
        ]);

        $oldText=$translation->text;
        $oldStatus=$translation->status;
        $translation->fill([
            'updated_by'=>$user->id,
            'text'=>$data['text']??null,
            'status'=>$data['status'],
        ])->save();

        TranslationRevision::create([
            'translation_id'=>$translation->id,
            'user_id'=>$user->id,
            'old_text'=>$oldText,
            'new_text'=>$translation->text,
            'old_status'=>$oldStatus,
            'new_status'=>$translation->status,
        ]);

        return back()->with('status','Tradução salva.');
    }
}
