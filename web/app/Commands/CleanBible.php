<?php

namespace App\Commands;

use App\Libraries\Scripture;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * One-off cleanup for Bibles imported before bible:import stripped the
 * source's {supplied words} and {marginal: notes}. Updates text in place so
 * verse ids (and users' highlights and bookmarks) are untouched.
 */
class CleanBible extends BaseCommand
{
    protected $group       = 'Prayer Journal';
    protected $name        = 'bible:clean';
    protected $description = 'Removes {braces} and marginal notes from already-imported KJV verses.';

    public function run(array $params)
    {
        $db   = Database::connect();
        $rows = $db->table('bible_verses')->select('id, text')->groupStart()->like('text', '{')->orLike('text', '}')->orLike('text', '«')->groupEnd()->get()->getResultArray();
        $db->transStart();
        foreach ($rows as $r) {
            $db->table('bible_verses')->where('id', $r['id'])->update(['text' => Scripture::cleanKjv($r['text'])]);
        }
        $db->transComplete();
        CLI::write('Cleaned ' . count($rows) . ' verses.', 'green');
    }
}
