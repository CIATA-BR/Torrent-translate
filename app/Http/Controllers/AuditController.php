<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\TranslationRevision;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $event = trim($request->string('event')->toString());

        $events = AuditEvent::query()
            ->with('user')
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->latest('id')
            ->paginate(50, ['*'], 'events_page')
            ->withQueryString();

        $revisions = TranslationRevision::query()
            ->with(['user', 'translation.source', 'translation.locale'])
            ->latest('id')
            ->paginate(25, ['*'], 'history_page')
            ->withQueryString();

        return view('admin.audit', [
            'events' => $events,
            'revisions' => $revisions,
            'eventFilter' => $event,
        ]);
    }
}
