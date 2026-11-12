<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Tlab\StoryblokTransfers\StoryblokTransferGenerator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('storyblok:generate', function () {
    $result = (new StoryblokTransferGenerator(
        spaceId: config('storyblok.space_id'),
        token: config('storyblok.management_token'),
        definitionsPath: config('storyblok.definitions_path'),
        outputPath: config('storyblok.output_path'),
        namespace: config('storyblok.namespace'),
        managementBaseUri: config('storyblok.management_base_uri'),
    ))->generate();

    foreach ($result->warnings as $warning) {
        $this->warn($warning);
    }

    $this->info(sprintf('Generated %d classes in %s.', count($result->componentNames), config('storyblok.output_path')));
})->purpose('Generate transfer classes from the Storyblok component schemas');
