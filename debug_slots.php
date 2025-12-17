
try {
    $count = \App\Models\Activity\TimeSlot::whereHas('activity', function($q){
        $q->where('access_type', 'group');
    })->count();
    echo "SLOTS_FOUND: " . $count . PHP_EOL;

    if ($count == 0) {
        echo "DEBUG: No slots found. Checking activities..." . PHP_EOL;
        $activities = \App\Models\Activity\Activity::where('access_type', 'group')->get();
        echo "Activities with access_type='group': " . $activities->count() . PHP_EOL;
        foreach($activities as $a) {
            echo " - ID: " . $a->activity_id . " Name: " . $a->name . PHP_EOL;
        }
        
        echo "DEBUG: Checking ALL access_types..." . PHP_EOL;
        $types = \App\Models\Activity\Activity::distinct()->pluck('access_type');
        echo json_encode($types) . PHP_EOL;
    } else {
        echo "DEBUG: Slots exist. Checking exclusivity..." . PHP_EOL;
        // Check how many are taken
        $taken = \App\Models\Activity\TimeSlot::whereHas('activity', fn($q) => $q->where('access_type', 'group'))
            ->has('partnerGroups')
            ->count();
        echo "SLOTS_TAKEN: " . $taken . PHP_EOL;
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
