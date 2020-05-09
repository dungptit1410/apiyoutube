<?php
namespace Facebook\WebDriver;
namespace App\Http\Controllers;
//namespace Facebook\WebDriver;
//namespace Facebook\WebDriver;
use Facebook\WebDriver\Firefox\FirefoxProfile;
use Facebook\WebDriver\Firefox\FirefoxDriver;
use Illuminate\Http\Request;
use KubAT\PhpSimple\HtmlDomParser;
use DB;
use YouTube\YouTubeDownloader;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use File;
//use YouTube\YouTubeStreamer();

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        echo "123";
    }
    public function video($videoID){
        $vid = $videoID; //the youtube video ID

        $youtubeURL = 'https://www.youtube.com/watch?v='.$videoID;
        $yt = new YouTubeDownloader();
        $youtube = new \YouTube\YouTubeStreamer();

        $links = $yt->getDownloadLinks($youtubeURL);
        //var_dump($links);
        $video = fopen($links[0]['url'], 'r');
        $file_name = public_path('video_file/video_'.$vid.'.mp4');
        $file = fopen($file_name,'w');
        stream_copy_to_stream($video,$file); //copy it to the file
        fclose($video);
        fclose($file);
        echo 'Youtube Video Download finished! Now check the file.';    
        //$youtube->stream($links[0]['url']);
    }

    public function testSpeechtoText(){
        $host = 'http://localhost:4444/wd/hub'; // this is the default
        $USE_FIREFOX = true; // if false, will use chrome.

        $profile = new FirefoxProfile();
        $profile->setPreference('general.useragent.override', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0');

       
        $caps = DesiredCapabilities::firefox();
        $profile->setPreference("dom.webdriver.enabled", false);
        $profile->setPreference('useAutomationExtension', false);
        $caps->setCapability(FirefoxDriver::PROFILE, $profile);
        
        $driver = RemoteWebDriver::create($host, $caps);

        $driver->get("https://speech-to-text-demo.ng.bluemix.net/");
        sleep(15);
        // $driver->switchTo()->frame($driver->findElement(WebDriverBy::cssSelector('.truste_box_overlay_inner iframe')));
        // $driver->findElement(WebDriverBy::className('call'))->click();
        // sleep(3);
        // $driver->switchTo()->defaultContent();
        //$file_name = '/public/video_file/track01.mp3';
         $file_name  =  public_path('video_file\track01.mp3');
        echo $file_name;
        $driver->findElement(WebDriverBy::cssSelector('input[type=file]'))->sendKeys($file_name);
        sleep(600);

    }


    public function getDataStatistics($videoID){
        $apikey = "AIzaSyDdR_LafAJnTvN02K_QP73H2SPlqWIniJo"; 
        $statisticsapi = "https://www.googleapis.com/youtube/v3/videos?part=statistics&id=" . $videoID . "&key=".$apikey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $statisticsapi);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);
        $value = json_decode(json_encode($data), true);
        return $value;
    }

    public function getData($keyword){
        $j = 0; 
        $apikey = "AIzaSyDdR_LafAJnTvN02K_QP73H2SPlqWIniJo"; 
        //$keyword = "abc";
        $pageToken = '';        
        $googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . $keyword . '&maxResults=25'. '&key=' . $apikey;
        $statistics_info = array();
        while ($j < 3) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $googleApiUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);
        $value = json_decode(json_encode($data), true);
        $pageToken = @$value['nextPageToken'];
        //if($value == null || $value == '') break;
        $data = array();
        if(isset($value['items'])){
        if(count($value['items']) > 0){
        for ($i = 0; $i < 25; $i++) {
            $videoId = @$value['items'][$i]['id']['videoId'];
           $title = @$value['items'][$i]['snippet']['title'];
           $img =  @$value['items'][$i]['snippet']['thumbnails']['high']['url'];
            $description = @$value['items'][$i]['snippet']['description'];
           $data['idvideo'] = $videoId;
           $data['title']  = $title;
           //$data['title'] = DB::connection()->getPdo()->quote(utf8_encode($data['title']));
           $data['img'] = $img;
           $data['description'] = $description;
           $data['keyword']    = $keyword;
           //$data['description'] = DB::connection()->getPdo()->quote(utf8_encode($data['description']));
           if($videoId != "" || $videoId != null){ DB::table('tbl_video')->insert($data);
           $statistics = $this->getDataStatistics($videoId);
           //var_dump($statistics['items'][0]['statistics']);
           $statistics_info['id_video'] =  $videoId;
           $statistics_info['viewCount'] = @$statistics['items'][0]['statistics']['viewCount'];
           $statistics_info['likeCount'] = @$statistics['items'][0]['statistics']['likeCount'];
           $statistics_info['dislikeCount'] = @$statistics['items'][0]['statistics']['dislikeCount'];
           $statistics_info['commentCount'] = @$statistics['items'][0]['statistics']['commentCount'];
           DB::table('tbl_statistics')->insert($statistics_info);
           //echo "insert success"."\n";
        }
        }   
        }
        }
        
        $googleApiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q='.$keyword . '&maxResults=25'. '&key=' . $apikey.'&pageToken='.$pageToken;
        $j++;
        echo $pageToken;
    }
}


    public function getList()
        {
            $dom = $this->getDom('https://www.youtube.com/results?search_query=pubg&sp=EgIIAw%253D%253D');
            // foreach ($dom->find('.container .col-lg-9 .post-title-box a.link') as $link) {
            //     echo $link->href . '<br>';
            // }
           
            $element = $dom->find('div[id=dismissable]',0);
            $khoi = $element->find('div[class=text-wrapper style-scope ytd-video-renderer]',0);
            $link = $khoi->find('div[id=meta]',0);
            $link2 = $link->find('div[id=title-wrapper]',0);
            $link3 = $link2->find('a',0);
            echo  $link3->href;
            //$list2 = $listitems->find('div[id=meta]',0);

            //$list_array = $list->find('a[title]');
            //echo $list_array;
            echo $list;            // for($i = 0 ; $i < sizeof($list_array); $i++){
            //     echo $list_array[$i]."<br>";
            // }
            //var_dump($dom);
        }
    public function getDom($link)
    {
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);// Khi thực thi lệnh sẽ k view ra trình duyệt mà lưu lại vào 1 biến kiểu string
        $content = curl_exec($ch);
        curl_close($ch);
        $dom = HtmlDomParser::str_get_html($content);

        return $dom;
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $url = 'https://www.retailmenot.com/sitemap/A';
        $data = $this->getContent($url);
        //var_dump($data);
        return view('index',compact('data'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function SaveHtml($start, $end) {
        $host = 'http://localhost:4444/wd/hub'; // this is the default
        $USE_FIREFOX = true; // if false, will use chrome.

        $profile = new FirefoxProfile();
        $profile->setPreference('general.useragent.override', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0');


        $caps = DesiredCapabilities::firefox();
        $profile->setPreference("dom.webdriver.enabled", false);
        $profile->setPreference('useAutomationExtension', false);
        $caps->setCapability(FirefoxDriver::PROFILE, $profile);

        $driver = RemoteWebDriver::create($host, $caps);
        $height = 1080;
        $width = 1920;
        $d = new WebDriverDimension($width,$height);
        $driver->manage()->window()->setSize($d);
        $list_page = DB::table('course_list')
        ->select('id','url')
        ->where('parent', '1')
        ->where('type', '2')
        ->where('status', '0')
        ->whereBetween('id', [$start,$end])
        ->get();
        // echo'<pre>';print_r($list_page);die;
        foreach ($list_page as $url) {
        $id_url = $url->id;
        $url = $url->url;
        // business,computer-science,data-science,information-technology,health,math-and-logic,personal-development,physical-science-and-engineering,social-sciences,language-learning
        // 136,105,67,30,59,18,41,51,65,19
        // for ($i = 2; $i<=65; $i++) {
        // $url = 'https://www.coursera.org/browse/social-sciences?page='.$i;
        // $url = 'https://www.coursera.org/learn/stem';
        $driver->get($url);
        sleep(5);
        $list = $driver->manage()->getCookies();
        $html_down = $driver->findElement(WebDriverBy::tagName('html'))->getAttribute('innerHTML');
        $html = $driver->findElements(WebDriverBy::cssSelector('div.BreadcrumbItem_1pp1zxi > a'));
        foreach ($html as $value) {
        $check = $value->getAttribute('data-reactid');
        if ($check == '237') {
        // $href_cate = $value->getAttribute('href');
        // $name_cate = parse_url($href_cate);
        // $name_cate = str_replace('/browse/', '', $name_cate['path']);
        $name_cate = strtolower($value->getText());
        $name_cate = str_replace(' ', '-', $name_cate);
        }
        }
        print_r($name_cate);
        $public_path = public_path();
        file_put_contents($public_path."/coursera/".$name_cate."_detail/".$id_url.".html", $html_down);
        DB::table('course_list')->where('url',$url)->update(['status' => '1']);
        }
        $driver->quit();
        // }
        }
}
