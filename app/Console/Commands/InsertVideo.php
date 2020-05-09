<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\YoutubeController;
class InsertVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:insert {keyword=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function rand_string($length) {
        $str = '';
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $size = strlen( $chars );
        for( $i = 0; $i < $length; $i++ ) {
        $str .= $chars[ rand( 0, $size - 1 ) ];
         }
        return $str;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(YoutubeController $course)
    {
        $count = $this->argument('keyword');
        
        //$course->getVideoRecentlyByKeyword($keyword);
        //$course->testSpeechtoText();
        for ($i=0; $i < $count; $i++) { 
            $keyword = $this->rand_string(4);
        $course->insertDetailVideo('EpSnOErH4o0');
            $course->getVideoRecentlyByKeyword($keyword);
        }
    }
}
