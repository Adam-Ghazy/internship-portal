<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PruneApplicationFiles extends Command
{
    protected $signature = 'applications:prune-files {--hours=48 : Minimum age, at least 24 hours} {--execute : Delete; otherwise dry run}';

    protected $description = 'Remove old unreferenced local application files; preserve draft, submission and group history';

    public function handle(): int
    {
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT);
        if ($hours === false || $hours < 24) {
            $this->error('The grace period must be at least 24 hours.');

            return self::FAILURE;
        }
        $cutoff = now()->subHours($hours);
        $count = 0;
        DB::table('recruitment.stored_files')->where('disk', 'local')
            ->where('object_key', 'like', 'private/applications/%')->where('created_at', '<', $cutoff)
            ->orderBy('id')->chunkById(100, function ($files) use ($cutoff, &$count) {
                foreach ($files as $candidate) {
                    DB::transaction(function () use ($candidate, $cutoff, &$count) {
                        // FOR UPDATE conflicts with FK key-share locks when a new reference is inserted.
                        $file = DB::table('recruitment.stored_files')->where('id', $candidate->id)
                            ->where('created_at', '<', $cutoff)->lockForUpdate()->first();
                        if (! $file || ! preg_match('~^private/applications/[A-Za-z0-9/_-]+\.pdf$~D', $file->object_key)) {
                            return;
                        }
                        foreach (['application_draft_documents', 'application_documents', 'group_documents'] as $table) {
                            if (DB::table('recruitment.'.$table)->where('stored_file_id', $file->id)->exists()) {
                                return;
                            }
                        }
                        $this->line(($this->option('execute') ? 'Removing ' : 'Would remove ').$file->object_key);
                        if ($this->option('execute')) {
                            $disk = Storage::disk('local');
                            if ($disk->exists($file->object_key) && ! $disk->delete($file->object_key)) {
                                throw new \RuntimeException('Unable to remove '.$file->object_key);
                            }
                            DB::table('recruitment.stored_files')->where('id', $file->id)->delete();
                        }
                        $count++;
                    });
                }
            });
        $this->info($count.' unreferenced files '.($this->option('execute') ? 'removed.' : 'eligible (dry run).'));

        return self::SUCCESS;
    }
}
