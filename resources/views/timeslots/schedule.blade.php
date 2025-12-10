@extends('layouts.app')
@section('title','Grille hebdomadaire')

@section('content')
<h2 class="text-2xl mb-4">Grille hebdomadaire</h2>

<div class="bg-white rounded-xl shadow border p-4 overflow-auto">
  <table class="min-w-full border-collapse">
    <thead>
      <tr>
        <th class="p-3 bg-gray-50">Heure</th>
        @foreach($weekdays as $d)
          <th class="p-3 bg-gray-50">{{ ucfirst($d->day_name) }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($ranges as $range)
        <tr>
          <td class="p-3 font-medium bg-gray-50">{{ $range['label'] }}</td>
          @foreach($weekdays as $d)
            @php
              $slot = $schedule[$d->weekday_id][$range['start'].'|'.$range['end']] ?? null;
            @endphp
            <td class="p-3 align-top border" style="min-width:140px;">
              @if($slot)
                <div class="rounded p-2" style="background: {{ $slot->activity->color_code ?? '#f3f4f6' }}">
                  <div class="font-semibold">{{ $slot->activity->name ?? '—' }}</div>
                  @if($slot->assigned_group)
                    <div class="text-sm text-gray-800">{{ $slot->assigned_group }}</div>
                  @endif
                  @if($slot->is_blocked)
                    <div class="text-xs text-red-700 mt-1">Bloqué / Entretien</div>
                  @endif
                </div>
              @else
                <div class="text-sm text-gray-400">Libre</div>
              @endif
            </td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
