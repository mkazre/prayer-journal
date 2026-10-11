<?php

namespace App\Modules\Bible\Controllers;

use App\Controllers\BaseController;
use App\Modules\Bible\Models\ReadingPlanModel;

class ReadingPlanController extends BaseController
{
    public function index(): string
    {
        return view('Bible/plans', [
            'title' => 'Reading plans',
            'plans' => (new ReadingPlanModel())->allWithProgress(auth()->loggedIn() ? $this->userId() : null),
        ]);
    }

    public function show(string $slug): string
    {
        $model = new ReadingPlanModel();
        $plan  = $model->where('slug', $slug)->first();
        if (! $plan) {
            $this->notFound();
        }
        $userPlan = null;
        $done     = [];
        if (auth()->loggedIn()) {
            $userPlan = $model->userPlans($this->userId())[(int) $plan['id']] ?? null;
            $done     = $userPlan ? $model->doneDays((int) $userPlan['id']) : [];
        }
        $nextDay = 1;
        while (in_array($nextDay, $done, true)) {
            $nextDay++;
        }

        return view('Bible/plan', [
            'title'    => $plan['title'],
            'plan'     => $plan,
            'days'     => $model->days((int) $plan['id']),
            'userPlan' => $userPlan,
            'done'     => $done,
            'nextDay'  => $nextDay,
        ]);
    }

    public function start(string $slug)
    {
        $plan = $this->plan($slug);
        $db   = db_connect();
        if (! $db->table('user_reading_plans')->where(['user_id' => $this->userId(), 'plan_id' => $plan['id']])->countAllResults()) {
            $db->table('user_reading_plans')->insert(['user_id' => $this->userId(), 'plan_id' => $plan['id'], 'started_at' => date('Y-m-d H:i:s')]);
        }

        return $this->done('Plan started — day 1 is ready.', [], site_url('bible/plans/' . $slug));
    }

    public function leave(string $slug)
    {
        $plan = $this->plan($slug);
        db_connect()->table('user_reading_plans')->where(['user_id' => $this->userId(), 'plan_id' => $plan['id']])->delete();

        return $this->done('You have left the plan.', [], site_url('bible/plans'));
    }

    public function toggleDay(string $slug, int $day)
    {
        $plan = $this->plan($slug);
        $db   = db_connect();
        $up   = $db->table('user_reading_plans')->where(['user_id' => $this->userId(), 'plan_id' => $plan['id']])->get()->getRowArray();
        if (! $up) {
            $db->table('user_reading_plans')->insert(['user_id' => $this->userId(), 'plan_id' => $plan['id'], 'started_at' => date('Y-m-d H:i:s')]);
            $up = $db->table('user_reading_plans')->where(['user_id' => $this->userId(), 'plan_id' => $plan['id']])->get()->getRowArray();
        }
        if ($day < 1 || $day > (int) $plan['days']) {
            $this->notFound();
        }
        $exists = $db->table('user_reading_plan_days')->where(['user_plan_id' => $up['id'], 'day' => $day])->countAllResults();
        if ($exists) {
            $db->table('user_reading_plan_days')->where(['user_plan_id' => $up['id'], 'day' => $day])->delete();
        } else {
            $db->table('user_reading_plan_days')->insert(['user_plan_id' => $up['id'], 'day' => $day, 'completed_at' => date('Y-m-d H:i:s')]);
            (new \App\Modules\Bible\Models\BibleReadingLogModel())->logToday($this->userId());
        }
        $doneCount = $db->table('user_reading_plan_days')->where('user_plan_id', $up['id'])->countAllResults();
        $db->table('user_reading_plans')->where('id', $up['id'])->update(['completed_at' => $doneCount >= (int) $plan['days'] ? date('Y-m-d H:i:s') : null]);

        return $this->done($exists ? 'Day ' . $day . ' unmarked.' : ($doneCount >= (int) $plan['days'] ? 'Plan complete — well done!' : 'Day ' . $day . ' complete.'), ['done' => ! $exists, 'count' => $doneCount]);
    }

    private function plan(string $slug): array
    {
        $plan = (new ReadingPlanModel())->where('slug', $slug)->first();
        if (! $plan) {
            $this->notFound();
        }

        return $plan;
    }
}
