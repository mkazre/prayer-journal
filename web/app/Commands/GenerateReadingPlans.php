<?php

namespace App\Commands;

use App\Libraries\Scripture;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Generates the built-in Bible reading plans from the imported books.
 * Safe to re-run: existing plans are updated in place (progress is kept
 * as long as the plan keeps its slug and day count).
 */
class GenerateReadingPlans extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'bible:plans';
    protected $description = 'Creates the built-in Bible reading plans (needs bible:import first).';

    public function run(array $params)
    {
        $db       = Database::connect();
        $chapters = [];
        foreach ($db->table('bible_books')->orderBy('position')->get()->getResultArray() as $b) {
            $chapters[(int) $b['position']] = (int) $b['chapter_count'];
        }
        if (count($chapters) !== 66) {
            CLI::error('Import the Bible first: php spark bible:import');

            return EXIT_ERROR;
        }

        $range = static function (int $from, int $to) use ($chapters): array {
            $out = [];
            for ($b = $from; $b <= $to; $b++) {
                for ($c = 1; $c <= $chapters[$b]; $c++) {
                    $out[] = [$b, $c];
                }
            }

            return $out;
        };

        $plans = [
            ['bible-in-a-year', 'The Bible in a Year', 'Read the whole Bible from Genesis to Revelation in 365 days — about three to four chapters a day.', 365, $range(1, 66)],
            ['new-testament-90', 'New Testament in 90 Days', 'Walk through the life of Jesus, the early church and the letters in three months.', 90, $range(40, 66)],
            ['gospels-30', 'The Gospels in 30 Days', 'Matthew, Mark, Luke and John — the life and words of Jesus in a month.', 30, $range(40, 43)],
            ['psalms-30', 'Psalms in 30 Days', 'Pray through all 150 psalms — five a day — and let them shape your prayer language.', 30, $range(19, 19)],
            ['proverbs-31', 'Proverbs: A Month of Wisdom', 'One chapter of Proverbs for every day of the month. "Wisdom is the principal thing" (Proverbs 4:7).', 31, $range(20, 20)],
        ];

        foreach ($plans as $pos => [$slug, $title, $desc, $days, $items]) {
            $existing = $db->table('reading_plans')->where('slug', $slug)->get()->getRowArray();
            if ($existing) {
                $planId = (int) $existing['id'];
                $db->table('reading_plans')->where('id', $planId)->update(['title' => $title, 'description' => $desc, 'days' => $days, 'position' => $pos]);
                $db->table('reading_plan_days')->where('plan_id', $planId)->delete();
            } else {
                $db->table('reading_plans')->insert(['slug' => $slug, 'title' => $title, 'description' => $desc, 'days' => $days, 'position' => $pos, 'created_at' => date('Y-m-d H:i:s')]);
                $planId = (int) $db->insertID();
            }

            // Spread chapters as evenly as possible across the days.
            $total = count($items);
            $rows  = [];
            for ($d = 0; $d < $days; $d++) {
                $start = (int) floor($d * $total / $days);
                $end   = (int) floor(($d + 1) * $total / $days);
                $slice = array_slice($items, $start, max(1, $end - $start));
                $rows[] = ['plan_id' => $planId, 'day' => $d + 1, 'label' => self::label($slice), 'readings' => json_encode($slice)];
            }
            $db->table('reading_plan_days')->insertBatch($rows);
            CLI::write("  + {$title}: {$days} days, {$total} chapters");
        }
        CLI::write(CLI::color('Reading plans ready.', 'green'));

        return EXIT_SUCCESS;
    }

    /**
     * "Genesis 1–3", or "Genesis 50 · Exodus 1–2" when crossing books.
     */
    private static function label(array $slice): string
    {
        $groups = [];
        foreach ($slice as [$b, $c]) {
            $groups[$b][] = $c;
        }
        $parts = [];
        foreach ($groups as $b => $cs) {
            $parts[] = Scripture::bookName($b) . ' ' . (count($cs) > 1 ? min($cs) . '–' . max($cs) : $cs[0]);
        }

        return implode(' · ', $parts);
    }
}
