@extends('layouts.app')
@section('title', 'Planning Global des Coachs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up" x-data="coachPlanning()">
    
    <!-- Top Action Bar (Matching Index Page) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Planning Coachs
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">
                Vue d'ensemble et gestion des affectations hebdomadaires.
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
             <!-- Legend -->
            <div class="inline-flex items-center gap-3 px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Assigné
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Non assigné
                </div>
            </div>

            <a href="{{ route('coaches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Retour Liste
            </a>
        </div>
    </div>

    <!-- Calendar Container (styled like Index 'Filters & Controls' block) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-[800px]">
        
        <!-- Header: Days -->
        <div class="flex-none grid grid-cols-7 border-b border-gray-200 bg-gray-50/50">
            <div class="w-14 bg-white border-r border-gray-100"></div> <!-- Time axis spacer -->
            @foreach($weekdays as $day)
            <div class="py-4 text-center border-r border-gray-100 last:border-r-0">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $day->day_name }}</span>
            </div>
            @endforeach
        </div>

        <div class="flex-1 flex overflow-hidden relative">
            <!-- Sidebar: Time Labels -->
            <div class="flex-none w-14 border-r border-gray-100 bg-white flex flex-col z-10">
                <div class="flex-1 overflow-hidden relative">
                    <div class="absolute inset-0 overflow-y-auto no-scrollbar" id="timeAxis">
                        <div class="h-3"></div>
                        @for($i = 7; $i <= 22; $i++)
                        <div class="h-24 text-right pr-2 relative">
                            <span class="text-xs font-medium text-gray-400 translate-y-[-50%] block">{{ sprintf('%02d:00', $i) }}</span>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="flex-1 overflow-y-auto custom-scrollbar relative bg-white" id="mainGrid" @scroll="syncScroll()">
                <div class="grid grid-cols-7 h-[calc(16*6rem)] relative w-full">
                    
                    <!-- Background Lines -->
                    <div class="absolute inset-0 flex flex-col pointer-events-none">
                         @for($i = 0; $i < 16; $i++)
                            <div class="h-24 border-b border-gray-50 w-full"></div>
                         @endfor
                    </div>
                    <!-- Vertical Lines -->
                    <div class="absolute inset-0 grid grid-cols-7 divide-x divide-gray-50 pointer-events-none">
                        @for($i = 0; $i < 7; $i++)
                        <div class="h-full"></div>
                        @endfor
                    </div>

                    <!-- Events -->
                    @foreach($weekdays as $day)
                    <div class="relative h-full border-r border-transparent last:border-r-0">
                        @foreach($slots->where('weekday_id', $day->weekday_id) as $slot)
                             @php
                                $startHour = (int)substr($slot->start_time, 0, 2);
                                $startMin = (int)substr($slot->start_time, 3, 2);
                                $endHour = (int)substr($slot->end_time, 0, 2);
                                $endMin = (int)substr($slot->end_time, 3, 2);
                                
                                $top = ($startHour - 7) * 6 + ($startMin / 60) * 6;
                                $height = (($endHour + $endMin/60) - ($startHour + $startMin/60)) * 6;
                            @endphp

                            <div 
                                @click="openModal({{ $slot->slot_id }}, {{ $slot->coach_id ?? 'null' }}, {{ $slot->assistant_coach_id ?? 'null' }}, '{{ addslashes($slot->activity->name) }}', '{{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}')"
                                class="absolute left-1 right-1 rounded-md border text-xs cursor-pointer transition-all duration-150 overflow-hidden hover:z-20 hover:brightness-95
                                {{ $slot->coach_id 
                                    ? 'bg-blue-50 border-blue-100 text-blue-900 shadow-sm' 
                                    : 'bg-amber-50/50 border-amber-200 border-dashed text-amber-800' 
                                }}"
                                style="top: {{ $top }}rem; height: {{ $height }}rem;"
                            >
                                <div class="absolute left-0 top-0 bottom-0 w-[3px] {{ $slot->coach_id ? 'bg-blue-500' : 'bg-amber-400' }}"></div>

                                <div class="p-1.5 pl-2.5 flex flex-col h-full relative">
                                    <div class="flex-1 min-h-0">
                                        <div class="font-bold truncate text-[11px] leading-snug">
                                            {{ $slot->activity->name }}
                                        </div>
                                        <div class="text-[10px] opacity-75 mt-0.5 font-medium">
                                            {{ substr($slot->start_time, 0, 5) }} - {{ substr($slot->end_time, 0, 5) }}
                                        </div>
                                    </div>

                                    @if($slot->coach)
                                    <div class="flex items-center gap-1 mt-1">
                                        <div class="w-5 h-5 rounded-full bg-white text-blue-700 flex items-center justify-center text-[9px] font-bold shadow-sm border border-blue-100">
                                            {{ substr($slot->coach->first_name, 0, 1) }}{{ substr($slot->coach->last_name, 0, 1) }}
                                        </div>
                                        @if($slot->assistantCoach)
                                        <div class="w-5 h-5 rounded-full bg-white text-purple-700 flex items-center justify-center text-[9px] font-bold shadow-sm border border-purple-100">
                                            {{ substr($slot->assistantCoach->first_name, 0, 1) }}
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Modal (Consistent with Index style) -->
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="isModalOpen" x-transition.opacity class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="isModalOpen = false"></div>
        
        <div x-show="isModalOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <div>
                   <h3 class="font-bold text-gray-900">Modifier l'affectation</h3> 
                   <p class="text-xs text-gray-500 mt-0.5" x-text="currentActivity + ' • ' + currentTime"></p>
                </div>
                <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Coach Principal</label>
                    <select x-model="selectedCoachId" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 px-3 bg-gray-50 focus:bg-white transition-colors">
                        <option value="">Sélectionner un coach...</option>
                        @foreach($coaches as $c)
                        <option value="{{ $c->staff_id }}">{{ $c->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Assistant <span class="text-gray-400 font-normal lowercase">(optionnel)</span></label>
                    <select x-model="selectedAssistantId" class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm py-2.5 px-3 bg-gray-50 focus:bg-white transition-colors">
                        <option value="">Aucun assistant</option>
                        @foreach($coaches as $c)
                        <option value="{{ $c->staff_id }}">{{ $c->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                 <div x-show="errorMessage" x-transition class="text-red-600 bg-red-50 px-3 py-2 rounded-lg text-xs font-medium border border-red-100 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span x-text="errorMessage"></span>
                 </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                <button @click="isModalOpen = false" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-all">Annuler</button>
                <button @click="saveAssignment()" :disabled="isLoading" class="px-6 py-2 bg-blue-600 border border-transparent text-white text-sm font-semibold rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-lg shadow-blue-200 disabled:opacity-70 disabled:cursor-not-allowed">
                    <span x-text="isLoading ? 'Enregistrement...' : 'Enregistrer'"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function coachPlanning() {
    return {
        isModalOpen: false,
        isLoading: false,
        currentSlotId: null,
        currentActivity: '',
        currentTime: '',
        selectedCoachId: '',
        selectedAssistantId: '',
        errorMessage: '',

        openModal(slotId, coachId, assistantId, activityName, timeRange) {
            this.currentSlotId = slotId;
            this.selectedCoachId = coachId || '';
            this.selectedAssistantId = assistantId || '';
            this.currentActivity = activityName;
            this.currentTime = timeRange;
            this.errorMessage = '';
            this.isModalOpen = true;
        },

        saveAssignment() {
            this.isLoading = true;
            this.errorMessage = '';

            fetch('{{ route("coaches.planning.update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    slot_id: this.currentSlotId,
                    coach_id: this.selectedCoachId,
                    assistant_coach_id: this.selectedAssistantId
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => { throw new Error(data.error || 'Erreur lors de la mise à jour'); });
                }
                return response.json();
            })
            .then(data => {
                window.location.reload();
            })
            .catch(error => {
                this.errorMessage = error.message;
                this.isLoading = false;
            });
        },
        
        syncScroll() {
            const mainGrid = document.getElementById('mainGrid');
            const timeAxis = document.getElementById('timeAxis');
            timeAxis.scrollTop = mainGrid.scrollTop;
        }
    }
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
