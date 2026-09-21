<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\TranslationRevision;
use App\Services\AuditTrail;
use App\Services\TranslationIntegrityValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TranslationReviewController extends Controller
{
    public function __construct(
        protected TranslationIntegrityValidator $validator,
        protected AuditTrail $audit
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
        $data = $request->validate([
            'text' => ['required', 'string'],
            'action' => ['required', 'in:approve,reject'],
            'version' => ['required', 'string', 'max:64'],
        ]);

        $text = trim($data['text']);

        DB::transaction(function () use ($request, $translation, $data, $text): void {
            $translation = Translation::query()
                ->with('source')
                ->lockForUpdate()
                ->findOrFail($translation->id);

            abort_unless($translation->status === 'pending_review', 409);

            if ($translation->updated_at?->toISOString() !== $data['version']) {
                throw ValidationException::withMessages([
                    'text' => [__('portal.translation_changed_reload')],
                ]);
            }

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

            $this->audit->record(
                $newStatus === 'approved' ? 'translation.approved' : 'translation.rejected',
                $request->user(),
                Translation::class,
                $translation->id,
                [
                    'source_id' => $translation->translation_source_id,
                    'locale_id' => $translation->locale_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            );
        });

        return back()->with(
            'status',
            $data['action'] === 'approve'
                ? __('portal.review_approved')
                : __('portal.review_rejected')
        );
    }

}
