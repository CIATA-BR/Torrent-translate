<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\TranslationRevision;
use App\Services\TranslationIntegrityValidator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TranslationReviewController extends Controller
{
    public function __construct(
        protected TranslationIntegrityValidator $validator
    ) {}

    public function index()
    {
        $translations = Translation::query()
            ->where('status', 'pending_review')
            ->with(['source', 'locale', 'updater'])
            ->latest('updated_at')
            ->paginate(25);

        return view('review.translations', [
            'translations' => $translations,
        ]);
    }

    public function update(Request $request, Translation $translation)
    {
        abort_unless($translation->status === 'pending_review', 409);

        $data = $request->validate([
            'text' => ['required', 'string'],
            'action' => ['required', 'in:approve,reject'],
        ]);

        $text = trim($data['text']);

        if ($data['action'] === 'approve') {
            $issues = $this->validator->validate($translation->source->msgid, $text);

            if ($issues !== []) {
                $messages = array_map(
                    fn (array $issue) => __($issue['key'], [
                        'source' => $issue['source'] === '' ? __('portal.validation_none') : $issue['source'],
                        'translation' => $issue['translation'] === '' ? __('portal.validation_none') : $issue['translation'],
                    ]),
                    $issues
                );

                throw ValidationException::withMessages(['text' => $messages]);
            }
        }

        $oldText = $translation->text;
        $oldStatus = $translation->status;
        $newStatus = $data['action'] === 'approve' ? 'approved' : 'rejected';

        $translation->fill([
            'updated_by' => $request->user()->id,
            'text' => $text,
            'status' => $newStatus,
        ])->save();

        TranslationRevision::query()->create([
            'translation_id' => $translation->id,
            'user_id' => $request->user()->id,
            'old_text' => $oldText,
            'new_text' => $translation->text,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        return back()->with(
            'status',
            $newStatus === 'approved'
                ? __('portal.review_approved')
                : __('portal.review_rejected')
        );
    }
}
