<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Catalog\Actions\CreatePlanAction;
use App\Modules\Catalog\Actions\UpdatePlanAction;
use App\Modules\Catalog\Actions\CreateActivityAction;
use App\Modules\Catalog\Actions\UpdateActivityAction;
use App\Modules\Catalog\DTOs\PlanData;
use App\Modules\Catalog\DTOs\ActivityData;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CatalogActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $createPlan;
    protected $updatePlan;
    protected $createActivity;
    protected $updateActivity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPlan = app(CreatePlanAction::class);
        $this->updatePlan = app(UpdatePlanAction::class);
        $this->createActivity = app(CreateActivityAction::class);
        $this->updateActivity = app(UpdateActivityAction::class);
    }

    public function test_can_create_and_update_plan()
    {
        // CREATE
        $planData = new PlanData(
            plan_name: 'Test Plan',
            description: 'Desc',
            plan_type: 'monthly_weekly',
            visits_per_week: 3,
            duration_months: 1,
            is_active: true
        );

        $plan = $this->createPlan->execute($planData);

        $this->assertDatabaseHas('pool_schema.plans', ['plan_name' => 'Test Plan']);
        $this->assertEquals(3, $plan->visits_per_week);

        // UPDATE
        $updateData = new PlanData(
            plan_name: 'Updated Plan',
            description: 'Desc',
            plan_type: 'monthly_weekly',
            visits_per_week: 5,
            duration_months: 1,
            is_active: true
        );

        $updatedPlan = $this->updatePlan->execute($plan, $updateData);
        
        $this->assertEquals('Updated Plan', $updatedPlan->plan_name);
        $this->assertEquals(5, $updatedPlan->visits_per_week);
    }

    public function test_can_create_and_update_activity()
    {
        // CREATE
        $actData = new ActivityData(
            name: 'Yoga',
            description: 'Relax',
            access_type: 'standard',
            color_code: '#ffffff',
            is_active: true
        );

        $activity = $this->createActivity->execute($actData);

        $this->assertDatabaseHas('pool_schema.activities', ['name' => 'Yoga']);

        // UPDATE
        $updateData = new ActivityData(
            name: 'Hot Yoga',
            description: 'Sweat',
            access_type: 'premium',
            color_code: '#ff0000',
            is_active: false
        );

        $updatedAct = $this->updateActivity->execute($activity, $updateData);

        $this->assertEquals('Hot Yoga', $updatedAct->name);
        $this->assertFalse($updatedAct->is_active);
    }
}
