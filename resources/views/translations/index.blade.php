@extends('layouts.app')
@section('title','Traduções')
@section('content')
<h1>Traduções</h1>
<form method="get" role="search" class="filters">
<div class="field"><label for="q">Buscar texto original</label><input id="q" name="q" type="search" value="{{ $search }}"></div>
<div class="field"><label for="filter">Filtro</label><select id="filter" name="filter"><option value="">Todas</option><option value="untranslated" @selected($filter==='untranslated')>Não traduzidas</option></select></div>
<button type="submit">Aplicar filtros</button>
</form>
@foreach($sources as $source)
@php($translation=$source->translations->first())
<article class="translation-card">
<h2>Entrada {{ $source->id }}</h2>
<p><strong>Original em inglês</strong></p><p lang="en">{{ $source->msgid }}</p>
<form method="post" action="{{ route('translations.store',$source) }}">@csrf
<div class="field"><label for="text-{{ $source->id }}">Tradução</label><textarea id="text-{{ $source->id }}" name="text" rows="4">{{ old('text',$translation?->text) }}</textarea></div>
<div class="field"><label for="status-{{ $source->id }}">Status</label><select id="status-{{ $source->id }}" name="status">@foreach(['draft'=>'Rascunho','review'=>'Revisão','approved'=>'Aprovada'] as $value=>$label)<option value="{{ $value }}" @selected(($translation?->status??'draft')===$value)>{{ $label }}</option>@endforeach</select></div>
<button type="submit">Salvar tradução</button>
</form>
</article>
@endforeach
{{ $sources->links() }}
@endsection
